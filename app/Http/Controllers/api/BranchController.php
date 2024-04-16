<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\BusinessBranchCoverImage;
use App\Models\Business;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateBranchLogoRequest;


class BranchController extends Controller
{
    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store', 'update', 'destroy','updateLogo']);

}

    /**
     * Display a listing of the resource.
     */
    public function index()
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Obtener todos los negocios asociados al usuario autenticado
        $businesses = Business::where('user_id', $userId)->pluck('id');

        // Obtener todas las sucursales asociadas a los negocios del usuario autenticado
        $businessBranches = BusinessBranch::whereIn('business_id', $businesses)->orderBy('id', 'desc')->get();

        // Verificar si se encontraron sucursales
        if ($businessBranches->isEmpty()) {
            // Si no se encontraron sucursales, devolver una respuesta 404
            return response()->json(['message' => 'No business branches found'], 404);
        }

        // Devolver las sucursales encontradas como respuesta JSON
        return response()->json(['message' => 'Business branches retrieved successfully', 'business_branches' => BranchResource::collection($businessBranches)], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción que ocurra durante el proceso
        return response()->json(['message' => 'Error retrieving business branches'], 500);
    }
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(BranchRequest $request)
{
    try {
        $data = $request->validated();

        // Asegurarse de que el business_id enviado pertenece al usuario autenticado
        if ($request->has('business_id')) {
            $business = Auth::user()->businesses()->where('id', $request->input('business_id'))->first();
            if (!$business) {
                return response()->json(['message' => 'Unauthorized. Business does not belong to authenticated user.'], 401);
            }
        } else {
            return response()->json(['message' => 'business_id is required.'], 400);
        }

        // Generar un UUID
        $data['branch_uuid'] = Uuid::uuid4()->toString();

        // Guardar la foto del negocio
        if ($request->hasFile('branch_logo')) {
            $photoPath = $this->storeImage($request->file('branch_logo'), 'public/branch_logo');
            $this->resizeImage(storage_path('app/'.$photoPath));
            $data['branch_logo'] = 'storage/app/'.$photoPath;
        }

        $business_branch = BusinessBranch::create($data);

        return $business_branch
            ? response()->json(['message' => 'Business Branch created successfully', 'business_branch' => new BranchResource($business_branch)], 201)
            : response()->json(['message' => 'Error creating business branch'], 500);
    } catch (\Exception $e) {
        // Manejo de excepciones
        return response()->json(['message' => 'An error occurred: '.$e->getMessage()], 500);
    }
}




public function updateLogo(UpdateBranchLogoRequest $request, $uuid)
{
    try {
        $business_branch = BusinessBranch::where('branch_uuid', $uuid)->firstOrFail();

       
        if ($request->hasFile('branch_logo')) {
            // Obtener el archivo de imagen
            $image = $request->file('branch_logo');

            // Eliminar la imagen anterior si existe
            if ($business_branch->branch_logo) {
                $this->deleteImage($business_branch->branch_logo);
            }

            // Guardar la nueva imagen
            $photoPath = $this->storeImage($image, 'public/branch_logo');
            $this->resizeImage(storage_path('app/'.$photoPath));

            // Actualizar la ruta de la imagen en el modelo Business
            $business_branch->branch_logo = 'storage/app/'.$photoPath;
            $business_branch->save();
        }

        // Devolver el recurso actualizado
        return response()->json(['message' => 'Business Branch Logo updated successfully', 'business_branch' => new BranchResource($business_branch)], 200);
    } catch (\Exception $e) {
        // Manejar el error
        return response()->json(['error' => 'Error updating business branch logo image'], 500);
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
    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
     {
         $business_branch = BusinessBranch::where('branch_uuid', $uuid)->first();
       
       return $business_branch
        ? response()->json(['message' => 'Business Branch retrieved successfully', 'business_branch' => new BranchResource($business_branch)], 200)
        : response()->json(['message' => 'Business Branch not found'], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BranchRequest $request, $uuid)
{
    // Obtener la sucursal por su UUID
    $business_branch = BusinessBranch::where('branch_uuid', $uuid)->first();

    if ($business_branch) {
        // Obtener el usuario autenticado
        $user = auth()->user();

        // Verificar si el business_id de la sucursal pertenece al usuario autenticado
        if ($user->businesses()->where('id', $request->business_id)->exists()) {
            // Actualizar la sucursal con los datos validados
            $business_branch->update($request->validated());

            // Devolver una respuesta JSON con la sucursal actualizada
            return response()->json(['message' => 'Business Branch updated successfully', 'business_branch' => new BranchResource($business_branch)], 200);
        } else {
            // El business_id de la sucursal no pertenece al usuario autenticado
            return response()->json(['message' => 'Unauthorized - Business Branch does not belong to the authenticated user'], 403);
        }
    } else {
        // La sucursal no fue encontrada
        return response()->json(['message' => 'Business Branch not found'], 404);
    }
}





    /**
     * Remove the specified resource from storage.
     */
    
public function destroy($uuid)
{
     $business_branch = BusinessBranch::where('branch_uuid', $uuid)->first();
    if ($business_branch) {
        // Eliminar el logotipo del negocio
        if ($business_branch->branch_logo) {
            $pathWithoutAppPublic = str_replace('storage/app/public/', '', $business_branch->branch_logo);
            Storage::disk('public')->delete($pathWithoutAppPublic);
        }

        // Obtener las im谩genes de portada asociadas al negocio desde el modelo BusinessCoverImage
        $coverImages = BusinessBranchCoverImage::where('branch_id', $business_branch->id)->get();

        if (!$coverImages->isEmpty()) {
            foreach ($coverImages as $image) {
                $pathWithoutAppPublic = str_replace('storage/app/public/', '', $image->branch_image_path);
                Storage::disk('public')->delete($pathWithoutAppPublic);
                $image->delete();
            }
        }

        // Eliminar el negocio
        $business_branch->delete();

        return response()->json(['message' => 'Business Branch and associated images deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'Business Branch not found'], 404);
    }
}
}
