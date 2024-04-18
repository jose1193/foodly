<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BusinessCoverImage;
use App\Models\User;
use App\Http\Requests\BusinessCoverImageRequest;
use App\Http\Resources\BusinessCoverImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateBusinessCoverImageRequest;

use App\Helpers\ImageHelper;

class BusinessCoverImageController extends Controller
{

     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store', 'edit', 'update', 'destroy']);

}

    public function index()
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = auth()->id();

        // Obtener todos los negocios asociados al usuario autenticado
        $businesses = User::findOrFail($userId)->businesses;

        // Inicializar un array para almacenar las imágenes de portada agrupadas por nombre de negocio
        $groupedCoverImages = [];

        // Iterar sobre cada negocio y obtener las imágenes de portada asociadas a cada uno
        foreach ($businesses as $business) {
            // Obtener el nombre del negocio
            $businessName = $business->business_name;

            // Obtener las imágenes de portada del negocio
            $coverImages = $business->coverImages;

            // Agregar las imágenes de portada al array asociado al nombre del negocio
            $groupedCoverImages[$businessName] = BusinessCoverImageResource::collection($coverImages);
        }

        // Devolver todas las imágenes de portada agrupadas por nombre de negocio como respuesta JSON
        return response()->json(['grouped_business_cover_images' => $groupedCoverImages], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error fetching business cover images'], 500);
    }
}


    
 public function store(BusinessCoverImageRequest $request)
{
    try {
        // Validar la solicitud entrante
        $validatedData = $request->validated();
        $businessImages = [];

        // Almacenar las imágenes de portada del negocio
        foreach ($validatedData['business_image_path'] as $image) {
            // Almacenar y redimensionar la imagen
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/business_photos');

            // Crear una nueva instancia de BusinessCoverImage y guardarla en la base de datos
            $businessCoverImage = BusinessCoverImage::create([
                'business_image_path' => $storedImagePath,
                'business_id' => $validatedData['business_id'],
                'business_image_uuid' => Uuid::uuid4()->toString(),
            ]);

            // Crear una instancia de BusinessCoverImageResource para la respuesta JSON
            $businessImages[] = new BusinessCoverImageResource($businessCoverImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Business cover images stored successfully',
            'business_cover_images' => $businessImages,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error storing business cover images'], 500);
    }
}



public function updateImage(UpdateBusinessCoverImageRequest $request, $uuid)
{
    try {
        $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('business_image_path')) {
            // Almacenar y redimensionar la nueva imagen
            $storedImagePath = ImageHelper::storeAndResize($request->file('business_image_path'), 'public/business_photos');

            // Eliminar la imagen anterior si existe
            $this->deleteOldImage($businessCoverImage->business_image_path);

            // Actualizar la ruta de la imagen en el modelo BusinessCoverImage
            $businessCoverImage->business_image_path = $storedImagePath;
            $businessCoverImage->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Business cover image updated successfully',
            'business_cover_images' => new BusinessCoverImageResource($businessCoverImage)
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error updating business cover image'], 500);
    }
}





private function deleteOldImage($oldImagePath)
{
    if ($oldImagePath) {
        $pathWithoutAppPublic = str_replace('storage/app/public/', '', $oldImagePath);
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }
}



    public function show($uuid)
{
    try {
        // Encontrar todas las imágenes de portada del negocio por su business_image_uuid
        $businessCoverImages = BusinessCoverImage::where('business_image_uuid', $uuid)->get();

        // Verificar si se encontraron imágenes de portada del negocio
        if ($businessCoverImages->isEmpty()) {
            return response()->json(['message' => 'Business cover images not found'], 404);
        }

        // Crear una colección de recursos para las imágenes de portada del negocio
        $businessCoverImagesResources = BusinessCoverImageResource::collection($businessCoverImages);

        // Devolver la colección de recursos de imágenes de portada del negocio bajo la clave 'images'
        return response()->json(['images' => $businessCoverImagesResources]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error retrieving business cover images'], 500);
    }
}





    public function update(BusinessCoverImageRequest $request, BusinessCoverImage $businessCoverImage)
    {
        $validatedData = $request->validated();

        $businessCoverImage->update($validatedData);

        return new BusinessCoverImageResource($businessCoverImage);
    }


    
    public function destroy($uuid)
{
    // Intentar encontrar la imagen de portada del negocio por su ID
    $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $uuid)->first();

    // Verificar si la imagen de portada del negocio fue encontrada
    if (!$businessCoverImage) {
        return response()->json(['message' => 'Business cover image not found'], 404);
    }

    // Eliminar la imagen del almacenamiento
    $pathWithoutAppPublic = str_replace('storage/app/public/', '', $businessCoverImage->business_image_path);
    if (Storage::disk('public')->exists($pathWithoutAppPublic)) {
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }

    // Eliminar el modelo de la base de datos
    $businessCoverImage->delete();

    return response()->json(['message' => 'Business cover image deleted successfully']);
}


   
}
