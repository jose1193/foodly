<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Business;
use App\Http\Requests\PromotionRequest;
use App\Http\Resources\PromotionResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;



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
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Validar los datos de la solicitud
        $validatedData = $request->validated();

        // Obtener el business_id proporcionado en la solicitud
        $businessId = $validatedData['business_id'];

        // Verificar si el business_id pertenece al usuario autenticado
        $userBusinessId = Business::where('user_id', $userId)->value('id');
        if ($businessId != $userBusinessId) {
            return response()->json(['message' => 'The provided business_id does not belong to the authenticated user'], 403);
        }

        // Generar un UUID para la promoción
        $validatedData['promotion_uuid'] = Uuid::uuid4()->toString();

        // Crear la promoción
        $promotion = Promotion::create($validatedData);

        return response()->json(['message' => 'Promotion created successfully', 'promotion' => new PromotionResource($promotion)], 201);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error creating promotion'], 500);
    }
}


public function update(PromotionRequest $request, $uuid)
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Buscar la promoción por el UUID y asegurarse de que pertenezca al usuario autenticado
        $promotion = Promotion::where('promotion_uuid', $uuid)
            ->whereHas('business', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        // Validar la solicitud
        $validatedData = $request->validated();

        // Verificar si el business_id proporcionado pertenece al usuario autenticado
        if (isset($validatedData['business_id']) && $validatedData['business_id'] != $promotion->business_id) {
            return response()->json(['message' => 'You are not authorized to update this promotion.'], 403);
        }

        // Actualizar los datos de la promoción
        $promotion->update($validatedData);

        // Devolver una respuesta JSON con la promoción actualizada
        return response()->json(['message' => 'Promotion updated successfully', 'promotion' => new PromotionResource($promotion)], 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en el que no se encuentre la promoción
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otra excepción que ocurra durante el proceso
        return response()->json(['message' => 'Error updating promotion'], 500);
    }
}




public function show($uuid)
{
    try {
        $promotion = Promotion::withTrashed()->where('promotion_uuid', $uuid)
            ->where('promotion_status', 'Active')
            ->firstOrFail();

        return response()->json(['promotion' => new PromotionResource($promotion)], 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
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
        return response()->json(['message' => 'Promotion restored successfully', 'promotion' => new PromotionResource($promotion)], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while restoring Promotion'], 500);
    }
}




}
