<?php

namespace App\Http\Controllers;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
        // Obtener el usuario autenticado con negocios y sus sucursales con imágenes de portada cargadas de antemano
        $user = Auth::user()->load('businesses.branches.coverImages');

        // Preparar un array para almacenar las imágenes de portada agrupadas por sucursal
        $groupedCoverImages = [];

        // Iterar sobre los negocios y sus sucursales para agrupar las imágenes de portada
        foreach ($user->businesses as $business) {
            foreach ($business->branches as $branch) {
                // Usar el nombre de la sucursal como clave para agrupar las imágenes de portada
                $branchName = $branch->branch_name; // Asegúrate de que 'branch_name' es el atributo correcto
                $groupedCoverImages[$branchName] = BusinessBranchCoverImageResource::collection($branch->coverImages);
            }
        }

        // Devolver una respuesta JSON con las imágenes de portada agrupadas por sucursal
        return response()->json($groupedCoverImages);
    } catch (\Exception $e) {
        // Registrar y manejar cualquier excepción que ocurra durante el proceso
        Log::error('Error in index function: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred during processing'], 500);
    }
}



    /**
     * Store a newly created resource in storage.
     */
    
public function store(BusinessBranchCoverImageRequest $request)
{
    DB::beginTransaction();  // Iniciar transacción
    try {
        $userId = Auth::id();
        $validatedData = $request->validated();

        $userBusinesses = Business::where('user_id', $userId)->pluck('id');
        if (!$userBusinesses->contains($validatedData['branch_id'])) {
            return response()->json(['error' => 'Invalid branch ID.'], 400);
        }

        $branchImages = [];
        foreach ($validatedData['branch_image_path'] as $image) {
            if ($image->isValid()) { // Asegurar que el archivo es válido
                $storedImagePath = ImageHelper::storeAndResize($image, 'public/branch_photos');

                $branchCoverImage = BranchCoverImage::create([
                    'branch_image_path' => $storedImagePath,
                    'branch_id' => $validatedData['branch_id'],
                    'branch_image_uuid' => Uuid::uuid4()->toString(),
                ]);

                $branchImages[] = new BusinessBranchCoverImageResource($branchCoverImage);
            } else {
                throw new \Exception("Invalid image file.");
            }
        }

        DB::commit();  // Confirmar transacción
        return response()->json(
            $branchImages,200
        );
    } catch (\Exception $e) {
        DB::rollBack();  // Revertir transacción en caso de error
        Log::error('An error occurred while storing branch cover images: ' . $e->getMessage());
        return response()->json(['error' => 'Error storing branch cover images'], 500);
    }
}



public function updateImage(UpdateBusinessBranchCoverImageRequest $request, $uuid)
{
    DB::beginTransaction(); // Iniciar transacción
    try {
        $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('branch_image_path')) {
            $storedImagePath = ImageHelper::storeAndResize(
                $request->file('branch_image_path'), 
                'public/branch_photos'
            );

            if ($branchCoverImage->branch_image_path) {
                $this->deleteOldImage($branchCoverImage->branch_image_path);
            }

            $branchCoverImage->update([
                'branch_image_path' => $storedImagePath
            ]);
        }

        DB::commit(); // Confirmar cambios si todo es correcto

        return response()->json(new BusinessBranchCoverImageResource($branchCoverImage));
    } catch (\Exception $e) {
        DB::rollBack(); // Revertir todos los cambios en caso de error
        Log::error('An error occurred while updating branch cover images: ' . $e->getMessage());
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
    // Validate the UUID format
    if (!Uuid::isValid($uuid)) {
        return response()->json(['error' => 'Invalid UUID format'], 400);
    }

    try {
        // Find the branch cover image by its branch_image_uuid
        $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();

        // Return the branch cover image
         return response()->json(new BusinessBranchCoverImageResource($branchCoverImage));
        
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Handle the exception and return an error response if the image is not found
        return response()->json(['message' => 'Branch cover image not found'], 404);
    } catch (\Exception $e) {
        // Handle any other exceptions and log the error
        Log::error('Failed to retrieve branch cover image: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to retrieve branch cover image'], 500);
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
    if (!Uuid::isValid($uuid)) {
        return response()->json(['error' => 'Invalid UUID format'], 400);
    }

    try {
        DB::beginTransaction();

        $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();
        $this->deleteBranchCoverImage($branchCoverImage->branch_image_path);
        $branchCoverImage->delete();

        DB::commit();

        return response()->json(['message' => 'Branch cover image deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to delete branch cover image'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting branch cover image: ' . $e->getMessage());
        return response()->json(['error' => 'Error deleting branch cover image: ' . $e->getMessage()], 500);
    }
}


protected function deleteBranchCoverImage($filePath)
{
    $path = str_replace('storage/app/public/', '', $filePath);
    if (Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }
}



}
