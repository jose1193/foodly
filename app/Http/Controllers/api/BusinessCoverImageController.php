<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BusinessCoverImage;
use App\Http\Requests\BusinessCoverImageRequest;
use App\Http\Resources\BusinessCoverImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateBusinessCoverImageRequest;


class BusinessCoverImageController extends Controller
{

     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store', 'edit', 'update', 'destroy']);

}

    public function index()
    {
        

         $businessCoverImages = BusinessCoverImage::orderBy('id', 'desc')->get();
        return response()->json(['business_image' => BusinessCoverImageResource::collection($businessCoverImages)]);
    
    }


    
 public function store(BusinessCoverImageRequest $request)
{
    try {
        // Validar la solicitud entrante
        $validatedData = $request->validated();
        $businessImages = [];

        // Almacenar las imágenes de portada del negocio
        foreach ($validatedData['business_image_path'] as $image) {
            $storedImagePath = $this->storeAndResizeImage($image);
            $businessCoverImage = BusinessCoverImage::create([
                'business_image_path' => $storedImagePath,
                'business_id' => $validatedData['business_id'],
                'business_image_uuid' => Uuid::uuid4()->toString(),
            ]);
            $businessImages[] = new BusinessCoverImageResource($businessCoverImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Business cover images stored successfully',
            'business_images' => $businessImages,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error storing business cover images'], 500);
    }
}



public function updateImage(UpdateBusinessCoverImageRequest $request, $cover_image_uuid)
{
    try {
        $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $cover_image_uuid)->firstOrFail();

        if ($request->hasFile('business_image_path')) {
            $storedImagePath = $this->storeAndResizeImage($request->file('business_image_path'));
            
            // Eliminar la imagen anterior si existe
            $this->deleteOldImage($businessCoverImage->business_image_path);

            // Actualizar la ruta de la imagen en el modelo BusinessCoverImage
            $businessCoverImage->business_image_path = $storedImagePath;
            $businessCoverImage->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Business cover image updated successfully',
            'business_images' => new BusinessCoverImageResource($businessCoverImage)
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error updating business cover image'], 500);
    }
}


private function storeAndResizeImage($image)
{
    $storedImagePath = $image->store('business_photos', 'public');
    $this->resizeImage(storage_path('app/public/' . $storedImagePath));
    return 'storage/app/public/' . $storedImagePath;
}

private function deleteOldImage($oldImagePath)
{
    if ($oldImagePath) {
        $pathWithoutAppPublic = str_replace('storage/app/public/', '', $oldImagePath);
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }
}

private function resizeImage($imagePath)
{
    $image = Image::make($imagePath);
    $originalWidth = $image->width();
    $originalHeight = $image->height();

    if ($originalWidth > 700 || $originalHeight > 700) {
        $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);
        $newWidth = $originalWidth * $scaleFactor;
        $newHeight = $originalHeight * $scaleFactor;
        $image->resize($newWidth, $newHeight);
    }

    $image->save($imagePath);
}



    public function show($cover_image_uuid)
{
    try {
        // Encontrar todas las imágenes de portada del negocio por su business_image_uuid
        $businessCoverImages = BusinessCoverImage::where('business_image_uuid', $cover_image_uuid)->get();

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


    
    public function destroy($cover_image_uuid)
{
    // Intentar encontrar la imagen de portada del negocio por su ID
    $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $cover_image_uuid)->first();

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
