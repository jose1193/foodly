<?php

namespace App\Http\Controllers;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

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
        $user = auth()->user()->load('businesses.coverImages');

        $groupedCoverImages = [];

        $user->businesses->each(function ($business) use ($user, &$groupedCoverImages) {
            if ($business->user_id === $user->id) {
             // Agrupar las imágenes por el nombre del negocio y usar el Resource para la transformación de datos
                $groupedCoverImages[$business->business_name] = BusinessCoverImageResource::collection($business->coverImages);
            }
        });

        return response()->json( $groupedCoverImages, 200);
    } catch (\Exception $e) {
        Log::error('Error fetching business cover images', [
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);
        return response()->json(['message' => 'Error fetching business cover images. Please try again later.'], 500);
    }
}





 public function store(BusinessCoverImageRequest $request)
{
    try {
        $validatedData = $request->validated();

        $businessImages = collect($validatedData['business_image_path'])->map(function ($image) use ($validatedData) {
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/business_photos');

            $businessCoverImage = BusinessCoverImage::create([
                'business_image_path' => $storedImagePath,
                'business_id' => $validatedData['business_id'],
                'business_image_uuid' => Uuid::uuid4()->toString(),
            ]);

            return new BusinessCoverImageResource($businessCoverImage);
        });

        return response()->json([
            'success' => true,
            //'message' => 'Business cover images stored successfully',
            'business_cover_images' => $businessImages,
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error storing business cover images: ' . $e->getMessage());
        return response()->json(['error' => 'Error storing business cover images: ' . $e->getMessage()], 500);
    }
}


public function updateImage(UpdateBusinessCoverImageRequest $request, $uuid)
{
    try {
        $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('business_image_path')) {
            DB::transaction(function () use ($request, $businessCoverImage) {
                // Almacenar y redimensionar la nueva imagen
                $storedImagePath = ImageHelper::storeAndResize($request->file('business_image_path'), 'public/business_photos');

                // Eliminar la imagen anterior si existe
                if ($businessCoverImage->business_image_path) {
                    $this->deleteOldImage($businessCoverImage->business_image_path);
                }

                // Actualizar la ruta de la imagen en el modelo BusinessCoverImage
                $businessCoverImage->business_image_path = $storedImagePath;
                $businessCoverImage->save();
            });
        }

        return response()->json(
           
            new BusinessCoverImageResource($businessCoverImage)
        );
    } catch (\Exception $e) {
        Log::error('Error updating business cover image: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business cover image: ' . $e->getMessage()], 500);
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
        // Validar el formato del UUID
        if (!Uuid::isValid($uuid)) {
            return response()->json(['error' => 'Invalid UUID format'], 400);
        }

        // Intentar encontrar la imagen de portada del negocio por su business_image_uuid
        $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $uuid)->firstOrFail();

        // Crear un recurso para la imagen de portada del negocio
        $businessCoverImageResource = new BusinessCoverImageResource($businessCoverImage);

        // Devolver el recurso de la imagen de portada del negocio
        return response()->json($businessCoverImageResource);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Business cover image not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error retrieving business cover image: ' . $e->getMessage());
        return response()->json(['error' => 'Error retrieving business cover image: ' . $e->getMessage()], 500);
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
    if (!Uuid::isValid($uuid)) {
        return response()->json(['error' => 'Invalid UUID format'], 400);
    }

    try {
        $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $uuid)->firstOrFail();
        $this->deleteFileFromStorage($businessCoverImage->business_image_path);
        $businessCoverImage->delete();

        // Correctly place the status code as the second parameter
        return response()->json(['message' => 'Business cover image deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Business cover image not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error deleting business cover image: ' . $e->getMessage());
        return response()->json(['error' => 'Error deleting business cover image: ' . $e->getMessage()], 500);
    }
}


protected function deleteFileFromStorage($filePath)
{
    $path = str_replace('storage/app/public/', '', $filePath);
    if (Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }
}



   
}
