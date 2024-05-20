<?php

namespace App\Http\Controllers;
use App\Models\BusinessBranch;
use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\BranchCoverImage;
use App\Models\Business;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateBranchLogoRequest;
use Illuminate\Support\Facades\Log;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

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
        // Obtain the authenticated user and their associated business IDs using Eloquent Relationships
        $user = auth()->user();
        $businesses = $user->businesses->pluck('id');

        // Check if user has businesses before proceeding
        if ($businesses->isEmpty()) {
            return response()->json(['message' => 'User has no associated businesses'], 404);
        }

        // Obtain all business branches associated with the user's businesses, including deleted ones
        $businessBranches = BusinessBranch::withTrashed()
                                           ->whereIn('business_id', $businesses)
                                           ->orderByDesc('id')
                                           ->get();

        // Check if business branches were found
        if ($businessBranches->isEmpty()) {
            return response()->json(['message' => 'No business branches found'], 404);
        }

        // Return the found business branches as a JSON response
        return response()->json(['business_branches' => BranchResource::collection($businessBranches)], 200);
    } catch (\Exception $e) {
        // Handle the error and log the error message
        Log::error('Error retrieving business branches: ' . $e->getMessage());
        return response()->json(['error' => 'Error retrieving business branches'], 500);
    }
}



    /**
     * Store a newly created resource in storage.
     */
public function store(BranchRequest $request)
{
    DB::beginTransaction(); // Iniciar la transacción

    try {
        $data = $request->validated();
        $businessId = $request->input('business_id');
        $user = auth()->user();
        $business = $user->businesses()->find($businessId);

        if (!$business) {
            return response()->json(['message' => 'Unauthorized. Business does not belong to authenticated user.'], 401);
        }

        $data['branch_uuid'] = Uuid::uuid4()->toString();
        $data['branch_logo'] = $this->handleBranchLogo($request);
        $data['business_id'] = $businessId;

        $businessBranch = BusinessBranch::create($data);

        DB::commit(); // Confirmar la transacción

        return response()->json(new BranchResource($businessBranch), 201);
    } catch (\Exception $e) {
        DB::rollback(); // Revertir la transacción en caso de error
        Log::error('Error storing branch: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred while storing the branch'], 500);
    }
}

private function handleBranchLogo($request)
{
    if ($request->hasFile('branch_logo')) {
        return ImageHelper::storeAndResize($request->file('branch_logo'), 'public/branch_logos');
    }
    return null; // Retornar null si no hay logo para mantener la coherencia de los datos
}




public function updateLogo(Request $request, $uuid)
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

            // Guardar la nueva imagen y obtener la ruta
            $photoPath = ImageHelper::storeAndResize($image, 'public/branch_logos');

            // Actualizar la ruta de la imagen en el modelo Branch
            $business_branch->branch_logo = $photoPath;
        }

        // Guardar los cambios en el modelo BusinessBranch
        $business_branch->save();

        // Devolver el recurso actualizado
         return response()->json(new BranchResource($business_branch), 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Business branch not found'], 404);
    } catch (\Exception $e) {
    // Manejar el error y registrar el mensaje de error
    Log::error('Error updating business branch logo image: ' . $e->getMessage());
    return response()->json(['error' => 'Error updating business branch logo image'], 500);
    }
    
}




private function deleteImage($imagePath)
{
    // Eliminar la imagen
    $pathWithoutAppPublic = str_replace('storage/app/public/', '', $imagePath);
    Storage::disk('public')->delete($pathWithoutAppPublic);
}


    /**
     * Display the specified resource.
     */
 public function show(string $uuid)
{
    try {
        // Obtener la sucursal del negocio por su UUID
        $business_branch = BusinessBranch::withTrashed()->where('branch_uuid', $uuid)->firstOrFail();
        
        // Devolver una respuesta JSON con la sucursal del negocio
        return response()->json(new BranchResource($business_branch), 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción que ocurra durante el proceso
        return response()->json(['message' => 'Business Branch not found'], 404);
    }
}


    /**
     * Update the specified resource in storage.
     */
   public function update(BranchRequest $request, $uuid)
{
    try {
        $user = auth()->user();

        // Optimiza la consulta para obtener la sucursal verificando directamente el acceso del usuario
        $business_branch = BusinessBranch::where('branch_uuid', $uuid)
                                         ->whereHas('business', function ($query) use ($user) {
                                             $query->where('user_id', $user->id);
                                         })->firstOrFail();

        // Actualizar la sucursal con los datos validados
        $business_branch->update($request->validated());

        // Devolver una respuesta JSON con la sucursal actualizada
        return response()->json(new BranchResource($business_branch), 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // La sucursal no fue encontrada o no pertenece al usuario
        return response()->json(['message' => 'Business Branch not found or access denied'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otro error y registrar el mensaje de error
        Log::error('Error updating business branch: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business branch'], 500);
    }
}





    /**
     * Remove the specified resource from storage.
     */
    
public function destroy($uuid)
{
    try {
        // Buscar la sucursal de negocio por su UUID
        $businessBranch = BusinessBranch::where('branch_uuid', $uuid)->firstOrFail();

        // Marcar la sucursal de negocio como eliminada (soft delete)
        $businessBranch->delete();

        return response()->json(['message' => 'Business Branch deleted successfully'], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // La sucursal no fue encontrada
        return response()->json(['message' => 'Business Branch not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while deleting Business Branch'], 500);
    }
}

public function restore($uuid)
{
    try {
        // Buscar la sucursal de negocio eliminada con el UUID proporcionado
        $businessBranch = BusinessBranch::where('branch_uuid', $uuid)->onlyTrashed()->firstOrFail();

        // Restaurar la sucursal de negocio
        $businessBranch->restore();

        // Devolver una respuesta JSON con el mensaje y el recurso de la sucursal de negocio restaurada
        return response()->json(new BranchResource($businessBranch), 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // La sucursal eliminada no fue encontrada
        return response()->json(['message' => 'Business Branch not found in trash'], 404);
    } catch (\Exception $e) {
    // Manejar cualquier excepción y devolver una respuesta de error
    Log::error('Error occurred while restoring Business Branch: ' . $e->getMessage());
    return response()->json(['message' => 'Error occurred while restoring Business Branch'], 500);
    }
    }


}