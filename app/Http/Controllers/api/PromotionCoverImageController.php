<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\PromotionImage;
use App\Models\User;
use App\Http\Requests\PromotionCoverImageRequest;
use App\Http\Resources\PromotionImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdatePromotionImageRequest;
use Illuminate\Support\Facades\Storage;


use App\Helpers\ImageHelper;

class PromotionCoverImageController extends Controller
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
    try {
        // Obtener el ID del usuario autenticado
        $userId = auth()->id();

        // Obtener todos los negocios asociados al usuario autenticado con carga ansiosa de promociones e imágenes
        $businesses = User::findOrFail($userId)->businesses()->with('promotions.promotionImages')->get();

        // Inicializar un array para almacenar las imágenes de promoción agrupadas por nombre de promoción
        $groupedPromotionImages = [];

        // Iterar sobre cada negocio y sus promociones para agrupar las imágenes de promoción
        foreach ($businesses as $business) {
            foreach ($business->promotions as $promotion) {
                $promotionTitle = $promotion->promotion_title;
                $promotionImages = $promotion->promotionImages;

                $groupedPromotionImages[$promotionTitle] = PromotionImageResource::collection($promotionImages);
            }
        }

        // Devolver todas las imágenes de promoción agrupadas por título de promoción como respuesta JSON
        return response()->json(['grouped_promotion_images' => $groupedPromotionImages], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error fetching promotion images'], 500);
    }
}




    /**
     * Store a newly created resource in storage.
     */
    public function store(PromotionCoverImageRequest $request)
{
    try {
        // Validar la solicitud entrante
        $validatedData = $request->validated();
        $promotionImages = [];

        // Almacenar las imágenes de portada del negocio
        foreach ($validatedData['promotion_image_path'] as $image) {
            // Almacenar y redimensionar la imagen
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/promotion_photos');

            // Crear una nueva instancia de PromotionImage y guardarla en la base de datos
            $promotionImage = PromotionImage::create([
                'promotion_image_path' => $storedImagePath,
                'promotion_id' => $validatedData['promotion_id'],
                'promotion_image_uuid' => Uuid::uuid4()->toString(),
            ]);

            // Crear una instancia de PromotionImageResource para la respuesta JSON
            $promotionImages[] = new PromotionImageResource($promotionImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Promotion images stored successfully',
            'promotion_images' => $promotionImages,
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error storing promotion images'], 500);
    }
}


    /**
     * Display the specified resource.
     */
    public function show($uuid)
{
    try {
        // Encontrar la imagen de promoción por su UUID
        $promotionImage = PromotionImage::where('promotion_image_uuid', $uuid)->first();

        // Verificar si se encontró la imagen de promoción
        if (!$promotionImage) {
            return response()->json(['message' => 'Promotion image not found'], 404);
        }

        // Crear un recurso para la imagen de promoción
        $promotionImageResource = new PromotionImageResource($promotionImage);

        // Devolver el recurso de imagen de promoción
        return response()->json(['promotion_image' => $promotionImageResource], 200);
    } catch (\Exception $e) {
        // Manejar errores de manera más detallada
        return response()->json(['error' => 'Error retrieving promotion image'], 500);
    }
}



    /**
     * Update the specified resource in storage.
     */

public function updateImage(UpdatePromotionImageRequest $request, $promotion_image_uuid)
{
    try {
        // Buscar la imagen de promoción por su UUID
        $promotionImage = PromotionImage::where('promotion_image_uuid', $promotion_image_uuid)->firstOrFail();

        if ($request->hasFile('promotion_image_path')) {
            // Almacenar y redimensionar la nueva imagen
            $storedImagePath = ImageHelper::storeAndResize($request->file('promotion_image_path'), 'public/promotion_photos');

            // Eliminar la imagen anterior si existe
            $this->deleteOldImage($promotionImage->promotion_image_path);

            // Actualizar la ruta de la imagen en el modelo PromotionImage
            $promotionImage->promotion_image_path = $storedImagePath;
            $promotionImage->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Promotion image updated successfully',
            'promotion_image' => new PromotionImageResource($promotionImage)
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error updating promotion image'], 500);
    }
}






private function deleteOldImage($oldImagePath)
{
    if ($oldImagePath) {
        $pathWithoutAppPublic = str_replace('storage/app/public/', '', $oldImagePath);
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }
}

    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($promotion_image_uuid)
{
    try {
        // Buscar la imagen de promoción por su UUID
        $promotionImage = PromotionImage::where('promotion_image_uuid', $promotion_image_uuid)->first();

        // Verificar si la imagen de promoción fue encontrada
        if (!$promotionImage) {
            return response()->json(['message' => 'Promotion image not found'], 404);
        }

        // Eliminar la imagen del almacenamiento
        $pathWithoutAppPublic = str_replace('storage/app/public/', '', $promotionImage->promotion_image_path);
        if (Storage::disk('public')->exists($pathWithoutAppPublic)) {
            Storage::disk('public')->delete($pathWithoutAppPublic);
        }

        // Eliminar el modelo de la base de datos
        $promotionImage->delete();

        return response()->json(['message' => 'Promotion image deleted successfully']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error occurred while deleting promotion image'], 500);
    }
}

}
