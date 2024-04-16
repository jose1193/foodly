<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Business;
use App\Http\Requests\BusinessRequest;
use App\Http\Resources\BusinessResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BusinessCoverImage;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateBusinessLogoRequest;

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
    } catch (\Exception $e) {
        // Manejar cualquier excepción que ocurra durante el proceso
        return response()->json(['message' => 'Error retrieving businesses'], 500);
    }
}


 public function show($uuid)
    {
         $business = Business::withTrashed()->where('business_uuid', $uuid)->first();
       
       return $business
        ? response()->json(['message' => 'Business retrieved successfully', 'business' => new BusinessResource($business)], 200)
        : response()->json(['message' => 'Business not found'], 404);
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
    // Obtener el negocio por su UUID
    $business = Business::where('business_uuid', $uuid)->first();

    if ($business) {
        // Verificar si el negocio pertenece al usuario autenticado
        if ($business->user_id === auth()->id()) {
            // Actualizar el negocio con los datos validados
            $business->update($request->validated());

            // Devolver una respuesta JSON con el negocio actualizado
            return response()->json(['message' => 'Business updated successfully', 'business' => new BusinessResource($business)], 200);
        } else {
            // El negocio no pertenece al usuario autenticado
            return response()->json(['message' => 'Unauthorized - Business does not belong to the authenticated user'], 403);
        }
    } else {
        // El negocio no fue encontrado
        return response()->json(['message' => 'Business not found'], 404);
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
        // Buscar el negocio eliminado con el UUID proporcionado
        $business = Business::where('business_uuid', $uuid)->onlyTrashed()->first();

        if (!$business) {
            return response()->json(['message' => 'Business not found in trash'], 404);
        }

        // Restaurar el negocio eliminado
        $business->restore();

        // Devolver una respuesta JSON con el negocio restaurado
        return response()->json(['message' => 'Business restored successfully', 'business' => new BusinessResource($business)], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while restoring Business'], 500);
    }
}









}
