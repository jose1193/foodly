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

        // Obtener todos los negocios asociados al usuario autenticado
        $businesses = Business::where('user_id', $userId)->orderBy('id', 'desc')->get();

        // Verificar si se encontraron negocios
        if ($businesses->isEmpty()) {
            // Si no se encontraron negocios, devolver una respuesta 404
            return response()->json(['message' => 'No businesses found'], 404);
        }

        // Devolver los negocios encontrados como respuesta JSON
        return response()->json(['message' => 'Businesses retrieved successfully', 'businesses' => BusinessResource::collection($businesses)], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción que ocurra durante el proceso
        return response()->json(['message' => 'Error retrieving businesses'], 500);
    }
}

 public function show($uuid)
    {
         $business = Business::where('business_uuid', $uuid)->first();
       
       return $business
        ? response()->json(['message' => 'Business retrieved successfully', 'business' => new BusinessResource($business)], 200)
        : response()->json(['message' => 'Business not found'], 404);
    }


   public function store(BusinessRequest $request)
{
    try {
        $data = $request->validated();

        // Generar un UUID
        $data['business_uuid'] = Uuid::uuid4()->toString();

        // Obtener el ID del usuario actualmente autenticado
        $data['user_id'] = Auth::id();

        // Guardar la foto del negocio
        if ($request->hasFile('business_logo')) {
            $photoPath = $this->storeImage($request->file('business_logo'), 'public/business_logos');
            $this->resizeImage(storage_path('app/'.$photoPath));
            $data['business_logo'] = 'storage/app/'.$photoPath;
        }

        $business = Business::create($data);

        return $business
            ? response()->json(['message' => 'Business created successfully', 'business' => new BusinessResource($business)], 201)
            : response()->json(['message' => 'Error creating business'], 500);
    } catch (\Exception $e) {
        // Manejo de excepciones
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

            // Guardar la nueva imagen
            $photoPath = $this->storeImage($image, 'public/business_logos');
            $this->resizeImage(storage_path('app/'.$photoPath));

            // Actualizar la ruta de la imagen en el modelo Business
            $business->business_logo = 'storage/app/'.$photoPath;
            $business->save();
        }

        // Devolver el recurso actualizado
        return new BusinessResource($business);
    } catch (\Exception $e) {
        // Manejar el error
        return response()->json(['error' => 'Error updating business logo image'], 500);
    }
}

private function storeImage($image, $storagePath)
{
    // Guardar la imagen
    $photoPath = $image->store($storagePath);
    return $photoPath;
}

private function deleteImage($imagePath)
{
    // Eliminar la imagen
    $pathWithoutAppPublic = str_replace('storage/app/public/', '', $imagePath);
    Storage::disk('public')->delete($pathWithoutAppPublic);
}



private function resizeImage($imagePath)
{
    // Redimensionar la imagen si es necesario
    $image = Image::make($imagePath);
    $originalWidth = $image->width();
    $originalHeight = $image->height();

    if ($originalWidth > 700 || $originalHeight > 700) {
        $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);
        $newWidth = $originalWidth * $scaleFactor;
        $newHeight = $originalHeight * $scaleFactor;
        $image->resize($newWidth, $newHeight);
        $image->save();
    }
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
    if ($business) {
        // Eliminar el logotipo del negocio
        if ($business->business_logo) {
            $pathWithoutAppPublic = str_replace('storage/app/public/', '', $business->business_logo);
            Storage::disk('public')->delete($pathWithoutAppPublic);
        }

        // Obtener las im谩genes de portada asociadas al negocio desde el modelo BusinessCoverImage
        $coverImages = BusinessCoverImage::where('business_id', $business->id)->get();

        if (!$coverImages->isEmpty()) {
            foreach ($coverImages as $image) {
                $pathWithoutAppPublic = str_replace('storage/app/public/', '', $image->business_image_path);
                Storage::disk('public')->delete($pathWithoutAppPublic);
                $image->delete();
            }
        }

        // Eliminar el negocio
        $business->delete();

        return response()->json(['message' => 'Business and associated images deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'Business not found'], 404);
    }
}








}
