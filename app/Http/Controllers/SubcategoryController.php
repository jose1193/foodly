<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Subcategory;
use App\Http\Requests\SubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use Ramsey\Uuid\Uuid;


class SubcategoryController extends Controller
{
     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['create', 'store', 'edit', 'update', 'destroy']);

}
    

    public function index()
{
    $subcategories = Subcategory::orderBy('id', 'desc')->get();
    return response()->json(['subcategories' => SubcategoryResource::collection($subcategories)]);
}

public function store(SubcategoryRequest $request)
{
    try {
        $validatedData = $request->validated();
        $validatedData['subcategory_uuid'] = Uuid::uuid4()->toString();
        
        $subcategory = Subcategory::create($validatedData);

        return response()->json(new SubcategoryResource($subcategory), 201);
    } catch (\Exception $e) {
        Log::error('Error creating subcategory: ' . $e->getMessage());
        
        return response()->json(['message' => 'Error creating subcategory'], 500);
    }
}



    public function show($uuid)
{
    try {
        // Encontrar la subcategoría por su UUID
        $subcategory = Subcategory::where('subcategory_uuid', $uuid)->firstOrFail();

        // Devolver una respuesta JSON con la subcategoría encontrada
        return response()->json(new SubcategoryResource($subcategory), 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en que la subcategoría no se encuentre
        return response()->json(['message' => 'Subcategory not found'], 404);
    }
}





  public function update(SubcategoryRequest $request, $uuid)
{
    try {
        // Encontrar la subcategoría por su UUID
        $subcategory = Subcategory::where('subcategory_uuid', $uuid)->firstOrFail();

        // Actualizar la subcategoría con los datos validados de la solicitud
        $subcategory->update($request->validated());

        // Devolver una respuesta JSON con la subcategoría actualizada
        return response()->json(new SubcategoryResource($subcategory), 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en que la subcategoría no se encuentre
        return response()->json(['message' => 'Subcategory not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otra excepción
        return response()->json(['message' => 'Error updating subcategory'], 500);
    }
}




public function destroy($uuid)
{
    // Encontrar la subcategoría por su ID
     $subcategory = Subcategory::where('subcategory_uuid', $uuid)->first();

    // Verificar si se encontró la subcategoría
    if (!$subcategory) {
        return response()->json(['message' => 'Subcategory not found'], 404);
    }

    // Eliminar la subcategoría
    $subcategory->delete();

    // Devolver una respuesta JSON con un mensaje de éxito
    return response()->json(['message' => 'Subcategory successfully removed'], 200);
}



}
