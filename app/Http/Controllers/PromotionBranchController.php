<?php

namespace App\Http\Controllers;
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
use Illuminate\Support\Facades\DB;


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

        // Obtener todas las promociones de las sucursales asociadas a los negocios del usuario autenticado
        $promotionsBranches = Business::where('user_id', $userId)
            ->with('businessBranch.promotionsbranches')
            ->get()
            ->pluck('businessBranch')
            ->flatten()
            ->pluck('promotionsbranches')
            ->flatten();

        // Devolver todas las promociones como respuesta JSON
        return response()->json(['branch_promotions' => $promotionsBranches], 200);
    } catch (\Exception $e) {
    return response()->json(['message' => 'Error fetching promotions: ' . $e->getMessage()], 500);
}

}





    /**
     * Store a newly created resource in storage.
     */
   public function store(PromotionBranchRequest $request)
{
    try {
        DB::beginTransaction();

        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Validar los datos de la solicitud
        $validatedData = $request->validated();

        // Obtener el business_branch_id proporcionado en la solicitud
        $businessBranchId = $validatedData['branch_id'];

        // Verificar si el business_branch_id pertenece al usuario autenticado
        $isUserBranch = BusinessBranch::where('id', $businessBranchId)
            ->whereHas('business', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->exists();

        if (!$isUserBranch) {
            return response()->json(['message' => 'The provided branch_id does not belong to the authenticated user'], 403);
        }

        // Generar un UUID para la promoción de la sucursal
        $validatedData['promotion_branch_uuid'] = Uuid::uuid4()->toString();

        // Crear la promoción de la sucursal
        $promotionBranch = PromotionBranch::create($validatedData);

        DB::commit();

        return response()->json(['message' => 'Promotion branch created successfully', 'branch_promotions' => new PromotionBranchResource($promotionBranch)], 201);
    } catch (\Exception $e) {
        DB::rollBack();
         return response()->json(['message' => 'Error fetching promotions: ' . $e->getMessage()], 500);
    }
}




    /**
     * Display the specified resource.
     */
    public function show($uuid)
{
    try {
        // Buscar la promoción de sucursal por su UUID, incluyendo las promociones eliminadas
        $promotionBranch = PromotionBranch::withTrashed()->where('promotion_branch_uuid', $uuid)->firstOrFail();

        // Devolver la promoción de sucursal como respuesta JSON
        return response()->json(new PromotionBranchResource($promotionBranch), 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en que la promoción no se encuentre y devolver un mensaje de error
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otra excepción y devolver un mensaje de error genérico
        return response()->json(['message' => 'Error fetching branch promotion  ' . $e->getMessage()], 500);
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
            ->whereHas('branches.business', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->first();

        if (!$promotionBranch) {
            return response()->json(['message' => 'Branch Promotion Not Found or Not Belonging to Authenticated User'], 404);
        }

        // Actualizar los atributos de la promoción de sucursal según la solicitud
        $promotionBranch->update($request->validated());

        return response()->json(new PromotionBranchResource($promotionBranch), 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error updating branch promotion: ' . $e->getMessage()], 500);
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

        // Verificar si la promoción eliminada existe en la papelera
        if (!$promotionBranch) {
            return response()->json(['message' => 'Promotion Branch not found in trash'], 404);
        }

        // Verificar si la promoción ya ha sido restaurada
        if (!$promotionBranch->trashed()) {
            return response()->json(['message' => 'Promotion Branch already restored'], 400);
        }

        // Restaurar la promoción eliminada
        $promotionBranch->restore();

        // Devolver una respuesta JSON con el mensaje y el recurso de la promoción restaurada
        return response()->json([
            'message' => 'Promotion Branch restored successfully',
            'branch_promotion' => new PromotionBranchResource($promotionBranch)
        ], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while restoring Promotion Branch'], 500);
    }
}





}
