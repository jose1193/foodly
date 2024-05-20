<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Business;
use App\Http\Requests\PromotionRequest;
use App\Http\Resources\PromotionResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
      // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store','update', 'destroy','updateLogo']);

}




   public function index()
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Obtener todos los negocios asociados al usuario autenticado
        $businesses = User::findOrFail($userId)->businesses;

        // Inicializar una colección vacía para almacenar todas las promociones
        $allPromotions = collect();

        // Iterar sobre cada negocio y obtener las promociones asociadas a cada uno
        foreach ($businesses as $business) {
            $businessId = $business->id;
            $promotions = Promotion::withTrashed()->where('business_id', $businessId)->get();
            $allPromotions = $allPromotions->concat($promotions);
        }

        // Devolver todas las promociones como respuesta JSON
        return response()->json(['promotions' => PromotionResource::collection($allPromotions)], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error fetching promotions'], 500);
    }
}


public function store(PromotionRequest $request)
{
    $validatedData = $request->validated();
    
    DB::beginTransaction();
    try {
        $this->authorizeBusiness($validatedData['business_id']);

        $validatedData['promotion_uuid'] = Uuid::uuid4()->toString();
        $promotion = Promotion::create($validatedData);

        DB::commit();

        return response()->json( new PromotionResource($promotion), 200);
    } catch (\Illuminate\Database\QueryException $e) {
        // Revertir la transacción en caso de error
        DB::rollBack();
        Log::error('Error creating promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error creating promotion', 'error' => $e->getMessage()], 500);
    }

    }

private function authorizeBusiness($businessId)
{
    $userId = Auth::id();
    $isUserBusiness = Business::where('user_id', $userId)->where('id', $businessId)->exists();

    if (!$isUserBusiness) {
        abort(403, 'The provided business_id does not belong to the authenticated user');
    }
}



public function update(PromotionRequest $request, $uuid)
{
    try {
        $userId = Auth::id();

        // Primeramente verificar si el usuario tiene autorización sobre el negocio antes de continuar
        $promotion = Promotion::where('promotion_uuid', $uuid)
                              ->whereHas('business', function ($query) use ($userId) {
                                  $query->where('user_id', $userId);
                              })->firstOrFail();

        $validatedData = $request->validated();

        // Verificación de la propiedad del business_id antes de realizar cualquier actualización
        if (isset($validatedData['business_id']) && $validatedData['business_id'] != $promotion->business_id) {
            return response()->json(['message' => 'You are not authorized to update this promotion.'], 403);
        }

        $promotion->update($validatedData);

        return response()->json(new PromotionResource($promotion), 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error updating promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating promotion'], 500);
    }
}



public function show($uuid)
{
    try {
        $promotion = Promotion::withTrashed()->where('promotion_uuid', $uuid)->firstOrFail();

        return response()->json(new PromotionResource($promotion), 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error fetching promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching promotion'], 500);
    }
}



public function destroy($uuid)
{
    try {
        $promotion = Promotion::where('promotion_uuid', $uuid)->firstOrFail();
        $promotion->delete();

        return response()->json(['message' => 'Promotion deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error deleting promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error deleting promotion'], 500);
    }
}



public function restore($uuid)
{
    try {
        // Buscar la promoción eliminada con el UUID proporcionado
        $promotion = Promotion::where('promotion_uuid', $uuid)->onlyTrashed()->first();

        if (!$promotion) {
            return response()->json(['message' => 'Promotion not found in trash'], 404);
        }

        // Restaurar la promoción eliminada
        $promotion->restore();

        // Devolver una respuesta JSON con el mensaje y el recurso de la promoción restaurada
        return response()->json(new PromotionResource($promotion), 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        Log::error('Error occurred while restoring Promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error occurred while restoring Promotion'], 500);
    }
}




}
