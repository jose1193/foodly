<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PromotionBranch;
use App\Models\User;
use App\Models\BusinessBranch;
use App\Models\Business;
use App\Http\Requests\PromotionBranchRequest;
use App\Http\Resources\PromotionBranchResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PromotionBranchController extends Controller
{
     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store','update', 'destroy','updateLogo']);

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
        $businesses = User::findOrFail($userId)->businesses;

        // Inicializar una colección vacía para almacenar todas las promociones de las sucursales
        $allPromotionsBranches = collect();

        // Iterar sobre cada negocio
        foreach ($businesses as $business) {
            // Obtener todas las sucursales del negocio
            $branches = $business->businessBranch;

            // Iterar sobre cada sucursal y obtener las promociones asociadas
                foreach ($branches as $branch) {
            // Obtener las promociones asociadas a esta sucursal
            $promotionsBranches = $branch->promotionsbranches;

        // Concatenar las promociones a la colección de promociones
        $allPromotionsBranches = $allPromotionsBranches->concat($promotionsBranches);
            }
        }

        // Devolver todas las promociones como respuesta JSON
        return response()->json(['branch_promotions' => $allPromotionsBranches->flatten()], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error fetching promotions'], 500);
    }
}






    /**
     * Store a newly created resource in storage.
     */
    public function store(PromotionBranchRequest $request)
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Validar los datos de la solicitud
        $validatedData = $request->validated();

        // Obtener el business_branch_id proporcionado en la solicitud
        $businessBranchId = $validatedData['branch_id'];

        // Verificar si el business_branch_id pertenece al usuario autenticado
        $userBusinessId = Business::whereHas('businessBranch', function ($query) use ($userId, $businessBranchId) {
            $query->where('id', $businessBranchId)
                  ->where('user_id', $userId);
        })->exists();

        if (!$userBusinessId) {
            return response()->json(['message' => 'The provided branch_id does not belong to the authenticated user'], 403);
        }

        // Generar un UUID para la promoción de la sucursal
        $validatedData['promotion_branch_uuid'] = Uuid::uuid4()->toString();

        // Crear la promoción de la sucursal
        $promotionBranch = PromotionBranch::create($validatedData);

        return response()->json(['message' => 'Promotion branch created successfully', 'branch_promotions' => new PromotionBranchResource($promotionBranch)], 201);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error creating promotion branch'], 500);
    }
}


    /**
     * Display the specified resource.
     */
    public function show($uuid)
{
    try {
        $promotionBranch = PromotionBranch::withTrashed()->where('promotion_branch_uuid', $uuid)->firstOrFail();

        return response()->json(['promotions_branches' => new PromotionBranchResource($promotionBranch)], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error fetching promotion'], 500);
    }
}


    /**
     * Update the specified resource in storage.
     */
    public function update(PromotionBranchRequest $request, $uuid)
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Buscar la promoción de sucursal por el UUID y asegurarse de que pertenezca al usuario autenticado
        $promotionBranch = PromotionBranch::where('promotion_branch_uuid', $uuid)
            ->whereHas('branches', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        
        $validatedData = $request->validated();

        // Actualizar los datos de la promoción de sucursal
        $promotionBranch->update($validatedData);

        // Devolver una respuesta JSON con la promoción de sucursal actualizada
        return response()->json(['message' => 'Promotion branch updated successfully', 'branch_promotions' => new PromotionBranchResource($promotionBranch)], 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en el que no se encuentre la promoción de sucursal
        return response()->json(['message' => 'Promotion branch not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otra excepción que ocurra durante el proceso
        return response()->json(['message' => 'Error updating promotion branch'], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
{
    try {
        $promotionBranch = PromotionBranch::where('promotion_branch_uuid', $uuid)->firstOrFail();
        $promotionBranch->delete();

        return response()->json(['message' => 'Promotion branch deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Promotion branch not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error deleting promotion branch'], 500);
    }
}




public function restore($uuid)
{
    try {
        // Buscar la promoción eliminada con el UUID proporcionado
        $promotionBranch = PromotionBranch::where('promotion_branch_uuid', $uuid)->onlyTrashed()->first();

        if (!$promotionBranch) {
            return response()->json(['message' => 'Promotion Branch not found in trash'], 404);
        }

        // Restaurar la promoción eliminada
        $promotionBranch->restore();

        // Devolver una respuesta JSON con el mensaje y el recurso de la promoción restaurada
        return response()->json(['message' => 'Promotion Branch restored successfully', 'branch_promotions' => new PromotionBranchResource($promotionBranch)], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while restoring Promotion Branch'], 500);
    }
}



}
