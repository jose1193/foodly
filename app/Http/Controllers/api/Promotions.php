<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Http\Resources\PromotionsResource;

class Promotions extends Controller
{
    /**
     * Display a listing of the resource for company.
     */
    public function list(string $company_id)
    {
        try {
            $promotions = Promotion::where('business_id', $company_id)->get();
            return response()->json($promotions, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las promociones',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'business_id' => 'required',
                'promotion_title' => 'required|String',
                'promotion_description' => 'required|String',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'promotion_type' => 'required|String',
                'promotion_status' => 'required|string',
                'discount_promotion' => 'required|String'
            ]);


            $promotion = Promotion::create($request->all());
            return $promotion ? response()->json([
                'message' => 'Promoción creada correctamente',
                'promotion' => new PromotionsResource($promotion)
            ], 201) :
                response()->json(['message' => 'Error al crear la promoción'], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear la promoción', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {

            $promotion = Promotion::findOrFail($id);
            return response()->json(['promotion' => new PromotionsResource($promotion)], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener la promoción', 'error' => $e->getMessage()], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $promotion = Promotion::findOrFail($id);

            // Validar los datos de entrada
            $promotion->update($request->all());

            return response()->json(['message' => 'Promoción actualizada correctamente', 'promotion' => new PromotionsResource($promotion)], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar la promoción', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            $promotion->delete();
            return response()->json(['message' => 'Promoción eliminada correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la promoción', 'error' => $e->getMessage()], 500);
        }
    }
}