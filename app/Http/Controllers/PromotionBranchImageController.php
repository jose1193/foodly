<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Models\PromotionBranchImage;
use App\Models\PromotionBranch;
use App\Models\User;
use App\Http\Requests\PromotionBranchImageRequest;
use App\Http\Resources\PromotionBranchImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdatePromotionBranchImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;

class PromotionBranchImageController extends Controller
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

        // Obtener todos los negocios asociados al usuario autenticado usando relaciones
        $user = User::with('businesses.branches.promotionsbranches.promotionBranchesImages')->findOrFail($userId);

        // Inicializar un array para almacenar las imágenes de promoción agrupadas por nombre de sucursal
        $groupedPromotionImages = [];

        // Iterar sobre cada negocio y obtener las imágenes de promoción asociadas a cada sucursal
        foreach ($user->businesses as $business) {
            // Iterar sobre cada sucursal y obtener las promociones y sus imágenes asociadas
            foreach ($business->branches as $branch) {
                $branchName = $branch->branch_name;

                $promotionImages = [];

                // Iterar sobre cada promoción y obtener sus imágenes asociadas
                foreach ($branch->promotionsbranches as $promotion) {
                    // Obtener las imágenes de promoción de la promoción
                    $images = $promotion->promotionBranchesImages;

                    // Agregar las imágenes de promoción al array asociado a la promoción
                    $promotionImages[$promotion->id] = PromotionBranchImageResource::collection($images);
                }

                // Agregar las promociones y sus imágenes asociadas al array asociado al nombre de la sucursal
                $groupedPromotionImages[$branchName] = $promotionImages;
            }
        }

        // Devolver todas las imágenes de promoción agrupadas por nombre de sucursal como respuesta JSON
        return response()->json(['grouped_promotion_images' => $groupedPromotionImages], 200);
    } catch (\Exception $e) {
        // Devolver un mensaje de error detallado en caso de excepción
        Log::error('Error fetching promotion images: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching promotion images: '], 500);
    }
}



    /**
     * Store a newly created resource in storage.
     */
     public function store(PromotionBranchImageRequest $request)
{
    try {
        // Iniciar una transacción de base de datos
        DB::beginTransaction();

        // Validar la solicitud entrante
        $validatedData = $request->validated();

        // Verificar si se enviaron archivos en la solicitud
        if (!$request->hasFile('promotion_branch_image_path')) {
            return response()->json(['error' => 'No images provided'], 422);
        }

        $promotionBranchImages = [];

        // Almacenar las imágenes de promoción de la sucursal
        foreach ($validatedData['promotion_branch_image_path'] as $image) {
            // Almacenar y redimensionar la imagen
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/promotion_branches_photos');

            // Crear una nueva instancia de PromotionBranchImage y guardarla en la base de datos
            $promotionBranchImage = PromotionBranchImage::create([
                'promotion_branch_image_path' => $storedImagePath,
                'promotion_branch_id' => $validatedData['promotion_branch_id'],
                'promotion_branch_image_uuid' => Uuid::uuid4()->toString(),
            ]);

            // Crear una instancia de PromotionImageResource para la respuesta JSON
            $promotionBranchImages[] = new PromotionBranchImageResource($promotionBranchImage);
        }

        // Confirmar la transacción
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Promotion Branch images stored successfully',
            'promotions_branches_images' => $promotionBranchImages,
        ], 201);
    } catch (\Exception $e) {
        // Revertir la transacción en caso de error
        DB::rollBack();

        // Manejar errores de manera más detallada
         Log::error('Error storing branch promotion images: ' . $e->getMessage());
        return response()->json(['Error storing branch promotion images'], 500);
    }
}


    /**
     * Display the specified resource.
     */
    public function show($uuid)
{
    try {
        // Encontrar la imagen de promoción por su uuid
        $promotionBranchImage = PromotionBranchImage::where('promotion_branch_image_uuid', $uuid)->first();

        // Verificar si se encontró la imagen de promoción
        if (!$promotionBranchImage) {
            return response()->json(['message' => 'Promotion Branch image not found'], 404);
        }

        // Crear un recurso para la imagen de promoción
        $promotionBranchImageResource = new PromotionBranchImageResource($promotionBranchImage);

        // Devolver el recurso de imagen de promoción
        return response()->json(['promotion_branch_image' => $promotionBranchImageResource], 200);
    } catch (\Exception $e) {
        // Manejar errores de manera más detallada
        Log::error('Error showing branch promotion image: ' . $e->getMessage());
        return response()->json(['Error showing branch promotion image'], 500);
    }
}

public function updateImage(UpdatePromotionBranchImageRequest $request, $uuid)
{
    try {
        // Buscar la imagen de promoción por su UUID
        $promotionImage = PromotionBranchImage::where('promotion_branch_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('promotion_branch_image_path')) {
            // Almacenar y redimensionar la nueva imagen
            $storedImagePath = ImageHelper::storeAndResize($request->file('promotion_branch_image_path'), 'public/promotion_branches_photos');

            // Eliminar la imagen anterior si existe
            $this->deleteOldImage($promotionImage->promotion_branch_image_path);

            // Actualizar la ruta de la imagen en el modelo PromotionImage
            $promotionImage->promotion_branch_image_path = $storedImagePath;
            $promotionImage->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Promotion Branch image updated successfully',
            'promotion_branch_image' => new PromotionBranchImageResource($promotionImage)
        ]);
    } catch (\Exception $e) {
        Log::error('An error occurred while updating promotion image: ' . $e->getMessage());
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
     public function destroy($uuid)
{
    try {
        // Buscar la imagen de promoción por su UUID
        $promotionBranchImage = PromotionBranchImage::where('promotion_branch_image_uuid', $uuid)->first();

        // Verificar si la imagen de promoción fue encontrada
        if (!$promotionBranchImage) {
            return response()->json(['message' => 'Promotion Branch image not found'], 404);
        }

        // Eliminar la imagen del almacenamiento
        $pathWithoutAppPublic = str_replace('storage/app/public/', '', $promotionBranchImage->promotion_branch_image_path);
        if (Storage::disk('public')->exists($pathWithoutAppPublic)) {
            Storage::disk('public')->delete($pathWithoutAppPublic);
        }

        // Eliminar el modelo de la base de datos
        $promotionBranchImage->delete();

        return response()->json(['message' => 'Promotion branch image deleted successfully']);
    } catch (\Exception $e) {
        Log::error('An error occurred while deleting branch promotion image: ' . $e->getMessage());
        return response()->json(['error' => 'Error occurred while deleting branch promotion image'], 500);
    }
}
}
