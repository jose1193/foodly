<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BranchCoverImage;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Http\Requests\BusinessBranchCoverImageRequest;
use App\Http\Resources\BusinessBranchCoverImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateBusinessBranchCoverImageRequest;
use Illuminate\Support\Facades\Auth;

use App\Helpers\ImageHelper;


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
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Obtener las sucursales asociadas a los negocios del usuario
        $businessBranches = BusinessBranch::whereHas('business', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with('coverImages')->get();

        // Inicializar un array para almacenar las imágenes de portada agrupadas por sucursal
        $groupedCoverImages = [];

        // Iterar sobre las sucursales y agrupar las imágenes de portada por sucursal
        foreach ($businessBranches as $branch) {
            // Obtener el nombre de la sucursal
            $branchName = $branch->branch_name; // Ajusta el nombre del atributo según la estructura de tu modelo
            // Agregar las imágenes de portada al array asociado a la sucursal utilizando el nombre como clave
            $groupedCoverImages[$branchName] = BusinessBranchCoverImageResource::collection($branch->coverImages);
        }

        // Devolver una respuesta JSON con las imágenes de portada agrupadas por sucursal
        return response()->json(['grouped_branch_cover_images' => $groupedCoverImages]);
    } catch (\Exception $e) {
        // Manejar cualquier excepción que ocurra durante el proceso
        return response()->json(['message' => 'An error occurred: '.$e->getMessage()], 500);
    }
}







    /**
     * Store a newly created resource in storage.
     */
    
public function store(BusinessBranchCoverImageRequest $request)
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Validar la solicitud entrante
        $validatedData = $request->validated();
        $branchImages = [];

        // Buscar todos los negocios asociados al usuario autenticado
        $userBusinesses = Business::where('user_id', $userId)->pluck('id');

        // Verificar si el branch_id pertenece a uno de los negocios del usuario
        if (!$userBusinesses->contains($validatedData['branch_id'])) {
            return response()->json(['error' => 'The provided branch ID does not belong to any business associated with the authenticated user'], 400);
        }

        // Almacenar las imágenes de portada del negocio
        foreach ($validatedData['branch_image_path'] as $image) {
            // Almacenar y redimensionar la imagen
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/branch_photos');

            // Crear una nueva instancia de BranchCoverImage y guardarla en la base de datos
            $branchCoverImage = BranchCoverImage::create([
                'branch_image_path' => $storedImagePath,
                'branch_id' => $validatedData['branch_id'],
                'branch_image_uuid' => Uuid::uuid4()->toString(),
            ]);

            // Crear una instancia de BusinessBranchCoverImageResource para la respuesta JSON
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
            // Almacenar y redimensionar la nueva imagen
            $storedImagePath = ImageHelper::storeAndResize($request->file('branch_image_path'), 'public/branch_photos');
            
            // Eliminar la imagen anterior si existe
            $this->deleteOldImage($branchCoverImage->branch_image_path);

            // Actualizar la ruta de la imagen en el modelo BranchCoverImage
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



private function deleteOldImage($oldImagePath)
{
    if ($oldImagePath) {
        $pathWithoutAppPublic = str_replace('storage/app/public/', '', $oldImagePath);
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }
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
