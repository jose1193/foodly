<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Auth;


class CategoryController extends Controller
{

    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['create', 'store', 'edit', 'update', 'destroy']);

}
    

    public function index()
{
    $categories = Category::orderBy('id', 'desc')->get();
    return response()->json(['categories' => CategoryResource::collection($categories)]);
}


    public function store(CategoryRequest $request)
{
    $validatedData = $request->validated();

    // Obtener el ID del usuario actualmente autenticado
    $userId = Auth::id();

    // Verificar si el usuario tiene el rol de "Permision Manager"
    if (Auth::user()->hasAnyRole(['Super Admin'])) {
    $validatedData['user_id'] = $userId;
    } else {
        // Si el usuario no tiene el rol adecuado, retornar un error
        return response()->json(['message' => 'You do not have permission to perform this action'], 403);
    }

    $category = Category::create($validatedData);

    return $category
        ? response()->json(['message' => 'Category created successfully', 'categories' => new CategoryResource($category)], 201)
        : response()->json(['message' => 'Error creating category'], 500);
}



   public function show($id)
{
    // Encontrar la categoría por su ID
    $category = Category::find($id);

    // Devolver una respuesta JSON con la categoría encontrada o un mensaje de error si no se encuentra
    return $category ? 
        response()->json(['categories' => new CategoryResource($category)]) :
        response()->json(['message' => 'Category not found'], 404);
}





  public function update(CategoryRequest $request, $id)
{
    // Encontrar la categoría por su ID
    $category = Category::find($id);

    // Verificar si se encontró la categoría
    if (!$category) {
        return response()->json(['message' => 'Category not found'], 404);
    }

    // Actualizar la categoría con los datos validados de la solicitud
    $category->update($request->validated());

    // Devolver una respuesta JSON con la categoría actualizada
    return $category ? 
        response()->json(['message' => 'Category updated successfully', 'category' => new CategoryResource($category)], 200) :
        response()->json(['message' => 'Error updating category'], 500);
}



public function destroy($id)
{
    // Encontrar la categoría por su ID
    $category = Category::find($id);

    // Verificar si se encontró la categoría
    if (!$category) {
        return response()->json(['message' => 'Category not found'], 404);
    }

    // Eliminar la categoría
    $category->delete();

    // Devolver una respuesta JSON con un mensaje de éxito
    return response()->json(['message' => 'Category successfully removed'], 200);
}



}
