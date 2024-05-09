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


use App\Helpers\ImageHelper;


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
        return response()->json(['message' => 'Businesses retrieved successfully', 'businesses' => BusinessResource::collection($businesses)], 200);
    } catch (\Illuminate\Database\QueryException $e) {
        // Manejar errores de consulta SQL
        return response()->json(['message' => 'Database error: ' . $e->getMessage()], 500);
    } catch (\Exception $e) {
        // Manejar cualquier otra excepci贸n que ocurra durante el proceso
        return response()->json(['message' => 'Error retrieving businesses'], 500);
    }
}


 public function show($uuid)
{
    try {
        // Obtener el negocio por su UUID, incluidos los eliminados
        $business = Business::withTrashed()->where('business_uuid', $uuid)->first();
        
        // Verificar si se encontr贸 el negocio
        if ($business) {
            // Si se encuentra el negocio, devolver una respuesta 200 con los datos del negocio
            return response()->json(['message' => 'Business retrieved successfully', 'business' => new BusinessResource($business)], 200);
        } else {
            // Si no se encuentra el negocio, devolver una respuesta 404
            return response()->json(['message' => 'Business not found'], 404);
        }
    } catch (\Illuminate\Database\QueryException $e) {
        // Manejar errores de consulta SQL
        return response()->json(['message' => 'Database error: ' . $e->getMessage()], 500);
    } catch (\Exception $e) {
        // Manejar cualquier otra excepci贸n que ocurra durante el proceso
        return response()->json(['message' => 'Error retrieving business'], 500);
    }
}


   public function store(BusinessRequest $request)
{
    // Validar la solicitud y obtener los datos validados
    $data = $request->validated();

    try {
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

        $user = $business->user;
         // Enviar correo electrónico al usuario
        Mail::to($user->email)->send(new WelcomeMailBusiness($user, $business));


        // Devolver una respuesta adecuada
        return $business
            ? response()->json(['message' => 'Business created successfully', 'business' => new BusinessResource($business)], 201)
            : response()->json(['message' => 'Error creating business'], 500);
    } catch (\Exception $e) {
        // Manejar errores inesperados
        return response()->json(['message' => 'An error occurred: '.$e->getMessage()], 500);
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
        return new BusinessResource($business);
    } catch (\Exception $e) {
        // Manejar el error
        return response()->json(['error' => $e->getMessage()], 500);
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
    // Obtener el negocio por su UUID asociado al usuario autenticado
    $business = auth()->user()->businesses()->where('business_uuid', $uuid)->first();

    if ($business) {
        // Actualizar el negocio con los datos validados
        $business->update($request->validated());

        // Devolver una respuesta JSON con el negocio actualizado
        return response()->json(['message' => 'Business updated successfully', 'business' => new BusinessResource($business)], 200);
    } else {
        // El negocio no fue encontrado o no pertenece al usuario autenticado
        return response()->json(['message' => 'Business not found or unauthorized'], 404);
    }
}




public function destroy($uuid)
{
    $business = Business::where('business_uuid', $uuid)->first();

    if (!$business) {
        return response()->json(['message' => 'Business not found'], 404);
    }

    // Marcar el negocio como eliminado
    $business->delete();

    return response()->json(['message' => 'Business deleted successfully'], 200);
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
        return response()->json([
            'message' => 'Business restored successfully',
            'business' => new BusinessResource($business)
        ], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepci贸n y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while restoring Business'], 500);
    }
}











}
