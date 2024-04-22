<?php

<<<<<<< HEAD
namespace App\Http\Controllers\api;
=======
namespace App\Http\Controllers\API;
>>>>>>> SocialLogin

use App\Http\Controllers\Controller;
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
    $validatedData = $request->validated();
    $validatedData['subcategory_uuid'] = Uuid::uuid4()->toString();
    $subcategory = Subcategory::create($validatedData);

    return $subcategory
        ? response()->json(['message' => 'Subcategory created successfully', 'subcategories' => new SubcategoryResource($subcategory)], 201)
        : response()->json(['message' => 'Error creating category'], 500);
}



    public function show($uuid)
{
    $subcategory = Subcategory::where('subcategory_uuid', $uuid)->first();
   
    if (!$subcategory) {
        return response()->json(['message' => 'Subcategory not found'], 404);
    }
    // Devolver una respuesta JSON con la subcategoría encontrada
    return response()->json(['subcategories' => new SubcategoryResource($subcategory)]);
}




   public function update(SubcategoryRequest $request, $uuid)
{
    // Encontrar la subcategoría por su ID
     $subcategory = Subcategory::where('subcategory_uuid', $uuid)->first();

    // Verificar si se encontró la subcategoría
    if (!$subcategory) {
        return response()->json(['message' => 'Subcategory not found'], 404);
    }

    // Actualizar la subcategoría con los datos validados de la solicitud
    $subcategory->update($request->validated());

    // Devolver una respuesta JSON con un mensaje de éxito y la subcategoría actualizada
    return $subcategory ? 
        response()->json(['message' => 'Subcategory updated successfully', 'subcategories' => new SubcategoryResource($subcategory)], 200) :
        response()->json(['message' => 'Error updating subcategory'], 500);
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
