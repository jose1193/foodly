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
        // Validate the incoming request
        $validatedData = $request->validated();
        
        // Initialize an array to store stored images
        $storedImages = [];
        
        // Store the business cover images
        foreach ($validatedData['business_image_path'] as $image) {
            // Store the uploaded image
            $storedImagePath = $image->store('business_photos', 'public');
            
            // Resize the image if necessary using Intervention Image
            $this->resizeImage($storedImagePath);
            
            // Create BusinessCoverImage model
            $businessCoverImage = BusinessCoverImage::create([
                'business_image_path' => 'app/public/'.$storedImagePath,
                'business_id' => $validatedData['business_id'],
            ]);

            // Add stored image to array
            $storedImages[] = new BusinessCoverImageResource($businessCoverImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Business cover images stored successfully',
            'images' => $storedImages,
        ]);
    }
    


    private function resizeImage($imagePath)
    {
        // Load the image using Intervention Image
        $image = Image::make(storage_path('app/public/' . $imagePath));

        // Get the width and height of the original image
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Check if the width or height are greater than 700 to resize
        if ($originalWidth > 700 || $originalHeight > 700) {
            // Calculate the scale factor to maintain the aspect ratio
            $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);

            // Calculate the new width and height for resizing the image
            $newWidth = $originalWidth * $scaleFactor;
            $newHeight = $originalHeight * $scaleFactor;

            // Resize the image
            $image->resize($newWidth, $newHeight);
        }

        // Save the resized image to the file system
        $image->save(storage_path('app/public/' . $imagePath));
    }


    public function show($business_id)
{
    // Encontrar todas las imágenes de portada del negocio por su business_id
    $businessCoverImages = BusinessCoverImage::where('business_id', $business_id)->get();

    // Verificar si se encontraron imágenes de portada del negocio
    if ($businessCoverImages->isEmpty()) {
        return response()->json(['message' => 'Business cover images not found'], 404);
    }

    // Crear una colección de recursos para las imágenes de portada del negocio
    $businessCoverImagesResources = BusinessCoverImageResource::collection($businessCoverImages);

    // Devolver la colección de recursos de imágenes de portada del negocio bajo la clave 'images'
    return response()->json(['images' => $businessCoverImagesResources]);
}




    public function update(BusinessCoverImageRequest $request, BusinessCoverImage $businessCoverImage)
    {
        $validatedData = $request->validated();

        $businessCoverImage->update($validatedData);

        return new BusinessCoverImageResource($businessCoverImage);
    }


    
    public function destroy($id)
{
    // Intentar encontrar la imagen de portada del negocio por su ID
    $businessCoverImage = BusinessCoverImage::find($id);

    // Verificar si la imagen de portada del negocio fue encontrada
    if (!$businessCoverImage) {
        return response()->json(['message' => 'Business cover image not found'], 404);
    }

    // Eliminar la imagen del almacenamiento
    $pathWithoutAppPublic = str_replace('app/public/', '', $businessCoverImage->business_image_path);
    if (Storage::disk('public')->exists($pathWithoutAppPublic)) {
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }

    // Eliminar el modelo de la base de datos
    $businessCoverImage->delete();

    return response()->json(['message' => 'Business cover image deleted successfully']);
}




   
}
