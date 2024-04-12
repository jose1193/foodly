<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BranchCoverImage;
use App\Http\Requests\BusinessBranchCoverImageRequest;
use App\Http\Resources\BusinessBranchCoverImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateBusinessBranchCoverImageRequest;

class BranchCoverImageController extends Controller
{
    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store', 'edit', 'update', 'destroy']);

}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        

         $branchCoverImages = BranchCoverImage::orderBy('id', 'desc')->get();
        return response()->json(['branch_cover_images' => BusinessBranchCoverImageResource::collection($branchCoverImages)]);
    
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(BusinessBranchCoverImageRequest $request)
{
    try {
        // Validar la solicitud entrante
        $validatedData = $request->validated();
        $branchImages = [];

        // Almacenar las imágenes de portada del negocio
        foreach ($validatedData['branch_image_path'] as $image) {
            $storedImagePath = $this->storeAndResizeImage($image);
            $branchCoverImage = BranchCoverImage::create([
                'branch_image_path' => $storedImagePath,
                'branch_id' => $validatedData['branch_id'],
                'branch_image_uuid' => Uuid::uuid4()->toString(),
            ]);
            $branchImages[] = new BusinessBranchCoverImageResource($branchCoverImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Branch cover images stored successfully',
            'branch_cover_images' => $branchImages,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error storing branch cover images'], 500);
    }
}


public function updateImage(UpdateBusinessBranchCoverImageRequest $request, $uuid)
{
    try {
        $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('branch_image_path')) {
            $storedImagePath = $this->storeAndResizeImage($request->file('branch_image_path'));
            
            // Eliminar la imagen anterior si existe
            $this->deleteOldImage($branchCoverImage->branch_image_path);

            // Actualizar la ruta de la imagen en el modelo branchCoverImage
            $branchCoverImage->branch_image_path = $storedImagePath;
            $branchCoverImage->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Branch cover image updated successfully',
            'branch_cover_images' => new BusinessBranchCoverImageResource($branchCoverImage)
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error updating business cover image'], 500);
    }
}


private function storeAndResizeImage($image)
{
    $storedImagePath = $image->store('branch_photos', 'public');
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

    /**
     * Display the specified resource.
     */
    public function show($uuid)
{
    try {
        // Encontrar la imagen de portada de la sucursal por su branch_image_uuid
        $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();

        // Devolver la imagen de portada de la sucursal
        return response()->json(['branch_image' => $branchCoverImage]);
    } catch (\Exception $e) {
        // Manejar la excepción y devolver una respuesta de error
        return response()->json(['message' => 'Failed to retrieve branch cover image', 'error' => $e->getMessage()], 500);
    }
}


    /**
     * Update the specified resource in storage.
     */
     public function update(BusinessBranchCoverImageRequest $request, BusinessBranchCoverImage $ranchCoverImage)
    {
        $validatedData = $request->validated();

        $branchCoverImage->update($validatedData);

        return new BusinessBranchCoverImageResource($businessCoverImage);
    }

    /**
     * Remove the specified resource from storage.
     */
     public function destroy($uuid)
{
    // Intentar encontrar la imagen de portada del negocio por su ID
    $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->first();

    // Verificar si la imagen de portada del negocio fue encontrada
    if (!$branchCoverImage) {
        return response()->json(['message' => 'Branch cover image not found'], 404);
    }

    // Eliminar la imagen del almacenamiento
    $pathWithoutAppPublic = str_replace('storage/app/public/', '', $branchCoverImage->branch_image_path);
    if (Storage::disk('public')->exists($pathWithoutAppPublic)) {
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }

    // Eliminar el modelo de la base de datos
    $branchCoverImage->delete();

    return response()->json(['message' => 'Branch cover image deleted successfully']);
}

}
