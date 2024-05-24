<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Business;

use App\Http\Requests\BusinessRequest;
use App\Http\Resources\BusinessResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BusinessCoverImage;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateBusinessLogoRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMailBusiness;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

use App\Helpers\ImageHelper;

use Illuminate\Support\Facades\DB;

class BusinessController extends Controller
{

     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store', 'edit', 'update', 'destroy','updateLogo']);

}

    public function index()
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Obtener todos los negocios asociados al usuario autenticado, incluidos los eliminados
        $businesses = Business::withTrashed()->where('user_id', $userId)->orderBy('id', 'desc')->get();

        // Verificar si se encontraron negocios
        if ($businesses->isEmpty()) {
            // Si no se encontraron negocios, devolver una respuesta 404
            return response()->json(['message' => 'No businesses found'], 404);
        }

        // Devolver los negocios encontrados como respuesta JSON, incluidos los eliminados
        return response()->json(['business' => BusinessResource::collection($businesses)], 200);
    } catch (QueryException $e) {
    // Manejar errores de consulta SQL y registrar el error
    Log::error('Database error: ' . $e->getMessage());
    return response()->json(['message' => 'Database error: ' . $e->getMessage()], 500);
} catch (\Exception $e) {
    // Manejar cualquier otra excepción que ocurra durante el proceso y registrar el error
    Log::error('Error retrieving business: ' . $e->getMessage());
    return response()->json(['message' => 'Error retrieving business'], 500);
}
}

public function show($uuid)
{
    try {
        // Obtener el negocio por su UUID, incluidos los eliminados
       $business = Business::withTrashed()->where('business_uuid', $uuid)->firstOrFail();
        
         return response()->json( new BusinessResource($business), 200);
    } catch (QueryException $e) {
    // Manejar errores de consulta SQL y registrar el error
    Log::error('Database error: ' . $e->getMessage());
    return response()->json(['message' => 'Database error: ' . $e->getMessage()], 500);
} catch (\Exception $e) {
    // Manejar cualquier otra excepción que ocurra durante el proceso y registrar el error
    Log::error('Error retrieving businesses: ' . $e->getMessage());
    return response()->json(['message' => 'Error retrieving business'], 500);
}
}

public function store(BusinessRequest $request)
{
    $data = $request->validated();

    try {
        return DB::transaction(function () use ($request, $data) {
            // Generar un UUID
            $data['business_uuid'] = Uuid::uuid4()->toString();

            // Obtener el ID del usuario actualmente autenticado
            $data['user_id'] = Auth::id();

            // Guardar la foto del negocio si existe
            if ($request->hasFile('business_logo')) {
                $image = $request->file('business_logo');
                $photoPath = ImageHelper::storeAndResize($image, 'public/business_logos');
                $data['business_logo'] = $photoPath;
            }

            // Crear el negocio
            $business = Business::create($data);

            // Verificar y ajustar el formato de business_services
            $services = $data['business_services'] ?? [];
            if (!is_array($services)) {
                $services = [$services];  // Convertir a array si no lo es
            }

            // Asociar servicios con el negocio usando la tabla pivot
            $business->services()->attach($services);

            // Enviar correo electrónico de manera asincrónica
            Mail::to($business->user->email)->queue(new WelcomeMailBusiness($business->user, $business));

            // Devolver una respuesta adecuada
            return response()->json(new BusinessResource($business), 200);
        });
    } catch (\Exception $exception) {
        // Manejo específico de excepciones fuera de la transacción
        Log::error('Error storing business: ' . $exception->getMessage());
        return response()->json(['message' => 'An error occurred: ' . $exception->getMessage()], 500);
    }
}








public function updateLogo(UpdateBusinessLogoRequest $request, $uuid)
{
    try {
        $business = Business::where('business_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('business_logo')) {
            // Obtener el archivo de imagen
            $image = $request->file('business_logo');

            // Eliminar la imagen anterior si existe
            if ($business->business_logo) {
                $this->deleteImage($business->business_logo);
            }

            // Guardar la nueva imagen y obtener la ruta
            $photoPath = ImageHelper::storeAndResize($image, 'public/business_logos');

            // Actualizar la ruta de la imagen en el modelo Business
            $business->business_logo = $photoPath;
            $business->save();
        }

        // Devolver el recurso actualizado
        return response()->json( new BusinessResource($business), 200);
    } catch (\Exception $e) {
    // Manejar el error y registrar el error
    Log::error('Error updating business logo: ' . $e->getMessage());
    return response()->json(['error' => 'An error occurred: '], 500);
    }
    }




private function deleteImage($imagePath)
{
    // Eliminar la imagen
    $pathWithoutAppPublic = str_replace('storage/app/public/', '', $imagePath);
    Storage::disk('public')->delete($pathWithoutAppPublic);
}


public function update(BusinessRequest $request, $uuid)
{
    try {
        // Iniciar la transacción
        return DB::transaction(function () use ($request, $uuid) {
            // Obtener el negocio por su UUID asociado al usuario autenticado
            $business = auth()->user()->businesses()->where('business_uuid', $uuid)->firstOrFail();

            // Actualizar el negocio con los datos validados
            $business->update($request->validated());

            // Sincronizar los servicios si están presentes y no vacíos en la solicitud
            if ($request->filled('business_services')) {
                $serviceIds = $request->input('business_services');
                $business->services()->sync($serviceIds);
            }

            // Devolver una respuesta JSON con el negocio actualizado
            return response()->json(new BusinessResource($business->fresh()), 200);
        });
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Si no se encuentra el negocio
        Log::warning("Business with UUID {$uuid} not found for user ID " . auth()->id());
        return response()->json(['message' => 'Business not found'], 404);
    } catch (\Exception $e) {
        // Manejar otros errores y registrar el error
        Log::error("Error updating business with UUID {$uuid}: " . $e->getMessage());
        return response()->json(['message' => 'An error occurred'], 500);
    }
}








public function destroy($uuid)
{
    try {
        // Obtener el negocio por su UUID
        $business = Business::where('business_uuid', $uuid)->firstOrFail();

        // Marcar el negocio como eliminado
        $business->delete();

        return response()->json(['message' => 'Business deleted successfully'], 200);
    } catch (\Exception $e) {
    // Manejar el error y registrar el error
        Log::error('Error deleting business: ' . $e->getMessage());
    return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}



public function restore($uuid)
{
    try {
        // Buscar el negocio eliminado con el UUID proporcionado asociado al usuario autenticado
        $business = auth()->user()->businesses()->where('business_uuid', $uuid)->onlyTrashed()->first();

        if (!$business) {
            return response()->json(['message' => 'Business not found in trash or unauthorized'], 404);
        }

        // Verificar si el negocio ya ha sido restaurado
        if (!$business->trashed()) {
            return response()->json(['message' => 'Business already restored'], 400);
        }

        // Restaurar el negocio eliminado
        $business->restore();

        // Devolver una respuesta JSON con el mensaje y el negocio restaurado
        return response()->json(new BusinessResource($business), 200);
    } catch (\Exception $e) {
    // Manejar el error y registrar el error
    Log::error('Error restoring business: ' . $e->getMessage());
    return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
}
}











}
