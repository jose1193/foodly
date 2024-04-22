<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
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

use App\Helpers\ImageHelper;

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
        // Obtener el usuario autenticado y sus negocios asociados con Eloquent Relationships
        $user = auth()->user();
        $businesses = $user->businesses->pluck('id');

        // Obtener todas las sucursales asociadas a los negocios del usuario autenticado, incluidas las eliminadas
        $businessBranches = BusinessBranch::withTrashed()->whereIn('user_id', $user->id)->orderByDesc('id')->get();

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
        // Validar la solicitud y obtener los datos validados
        $data = $request->validated();

        // Asegurarse de que el business_id enviado pertenece al usuario autenticado
        $businessId = $request->input('business_id');
        $user = auth()->user();
        $business = $user->businesses()->find($businessId);
        if (!$business) {
            return response()->json(['message' => 'Unauthorized. Business does not belong to authenticated user.'], 401);
        }

        // Generar un UUID para la sucursal del negocio
        $data['branch_uuid'] = Uuid::uuid4()->toString();

        // Guardar la foto del branch si existe
        if ($request->hasFile('branch_logo')) {
            $photoPath = ImageHelper::storeAndResize($request->file('branch_logo'), 'public/branch_logos');
            $data['branch_logo'] = $photoPath;
        }

        // Asignar el business_id al dato (por seguridad adicional)
        $data['business_id'] = $businessId;

        // Crear la sucursal del negocio
        $businessBranch = BusinessBranch::create($data);

        // Devolver una respuesta adecuada
        return response()->json(['message' => 'Business Branch created successfully', 'business_branch' => new BranchResource($businessBranch)], 201);
    } catch (\Exception $e) {
        // Manejar errores inesperados
        return response()->json(['message' => 'An error occurred: '.$e->getMessage()], 500);
    }
}




public function updateLogo(Request $request, $uuid)
{
    try {
        $business_branch = BusinessBranch::where('branch_uuid', $uuid)->firstOrFail();

        // Validar el archivo de imagen
        if ($request->hasFile('branch_logo') && $request->file('branch_logo')->isValid()) {
            // Eliminar la imagen anterior si existe
            if ($business_branch->branch_logo) {
                $this->deleteImage($business_branch->branch_logo);
            }

            // Guardar la nueva imagen
            $photoPath = $this->storeAndResizeImage($request->file('branch_logo'));
            $business_branch->branch_logo = $photoPath;
        }

        // Guardar cambios en el modelo Business Branch
        $business_branch->save();

        // Devolver el recurso actualizado
        return response()->json(['message' => 'Business Branch Logo updated successfully', 'business_branch' => new BranchResource($business_branch)], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Business branch not found'], 404);
    } catch (\Exception $e) {
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
         $business_branch = BusinessBranch::withTrashed()->where('branch_uuid', $uuid)->first();
       
       return $business_branch
        ? response()->json(['message' => 'Business Branch retrieved successfully', 'business_branch' => new BranchResource($business_branch)], 200)
        : response()->json(['message' => 'Business Branch not found'], 404);
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(BranchRequest $request, $uuid)
{
    try {
        // Obtener la sucursal por su UUID
        $business_branch = BusinessBranch::where('branch_uuid', $uuid)->firstOrFail();

        // Obtener el usuario autenticado
        $user = auth()->user();

        // Verificar si el usuario es propietario de la sucursal
        if (!$user->businesses()->where('id', $business_branch->business_id)->exists()) {
            // El usuario no es propietario de la sucursal
            return response()->json(['message' => 'Unauthorized - Business Branch does not belong to the authenticated user'], 403);
        }

        // Actualizar la sucursal con los datos validados
        $business_branch->update($request->validated());

        // Devolver una respuesta JSON con la sucursal actualizada
        return response()->json(['message' => 'Business Branch updated successfully', 'business_branch' => new BranchResource($business_branch)], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // La sucursal no fue encontrada
        return response()->json(['message' => 'Business Branch not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otro error
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
        return response()->json(['message' => 'Business Branch restored successfully', 'business_branch' => new BranchResource($businessBranch)], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // La sucursal eliminada no fue encontrada
        return response()->json(['message' => 'Business Branch not found in trash'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while restoring Business Branch'], 500);
    }
}


}