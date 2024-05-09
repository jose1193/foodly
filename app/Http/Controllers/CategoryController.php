<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateCategoryImageRequest;
use Illuminate\Support\Facades\Log;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class CategoryController extends Controller
{

    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['create', 'store', 'edit', 'update', 'destroy','updateImage']);

}
    

    public function index()
{
    $categories = Category::orderBy('id', 'desc')->get();
    return response()->json(['categories' => CategoryResource::collection($categories)]);
}


 public function store(CategoryRequest $request)
{
    $validatedData = $this->prepareData($request);

    try {
        $category = $this->createCategory($validatedData);
        $this->handleCategoryImage($request, $category);

        return $this->successfulResponse($category);
    } catch (\Throwable $e) {
        Log::error('An error occurred while creating category: ' . $e->getMessage());
        return $this->errorResponse();
    }
}

private function prepareData($request)
{
    return array_merge($request->validated(), [
        'user_id' => Auth::id(),
        'category_uuid' => Uuid::uuid4()->toString()
    ]);
}

private function createCategory($data)
{
    return Category::create($data);
}

private function handleCategoryImage($request, $category)
{
    if ($request->hasFile('category_image_path')) {
        $imagePath = ImageHelper::storeAndResize($request->file('category_image_path'), 'public/categories_images');
        $category->category_image_path = $imagePath;
        $category->save();
    }
}

private function successfulResponse($category)
{
    return response()->json([
        'message' => 'Category created successfully',
        'categories' => new CategoryResource($category)
    ], 200);
}

private function errorResponse()
{
    return response()->json(['error' => 'An error occurred while creating category'], 500);
}




public function updateImage(UpdateCategoryImageRequest $request, $uuid)
{
    try {
        $category = Category::where('category_uuid', $uuid)->firstOrFail();

        // Guardar la imagen si está presente
        if ($request->hasFile('category_image_path')) {
            // Obtener el archivo de imagen
            $image = $request->file('category_image_path');

            // Eliminar la imagen anterior si existe
            if ($category->category_image_path) {
                $pathWithoutAppPublic = str_replace('storage/app/public/', '', $category->category_image_path);
                Storage::disk('public')->delete($pathWithoutAppPublic);
            }

            // Guardar y redimensionar la nueva imagen utilizando ImageHelper
            $photoPath = ImageHelper::storeAndResize($image, 'public/categories_images');

            // Actualizar la ruta de la imagen en el modelo Category
            $category->category_image_path = 'storage/app/' . $photoPath;
            $category->save();
        }

        // Devolver el recurso actualizado
        
        return response()->json( new CategoryResource($category), 200);
       
    } catch (\Exception $e) {
    // Manejar el error y registrar el mensaje de error si es necesario
    Log::error('Error updating category image: ' . $e->getMessage());
    return response()->json(['error' => 'Error updating category image'], 500);
    }
    }




public function show($uuid)
{
    try {
        // Encontrar la categoría por su UUID
        $category = Category::where('category_uuid', $uuid)->firstOrFail();

        // Devolver una respuesta JSON con la categoría encontrada
        return response()->json(new CategoryResource($category), 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en que la categoría no se encuentre
        return response()->json(['message' => 'Category not found'], 404);
    }
}




 public function update(CategoryRequest $request, $uuid)
{
    try {
        // Encontrar la categoría por su UUID
        $category = Category::where('category_uuid', $uuid)->firstOrFail();

        // Actualizar la categoría con los datos validados de la solicitud
        $category->update($request->validated());

        // Devolver una respuesta JSON con la categoría actualizada
        return response()->json(new CategoryResource($category), 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Category not found'], 404);
    } catch (\Exception $e) {
    // Manejar cualquier excepción y devolver una respuesta de error
    Log::error('Error updating category: ' . $e->getMessage());
    return response()->json(['message' => 'Error updating category'], 500);
    }
    }



public function destroy($uuid)
{
    try {
        // Encontrar la categoría por su UUID o lanzar una excepción si no se encuentra
        $category = Category::where('category_uuid', $uuid)->firstOrFail();

        // Eliminar las imágenes asociadas si existen
        if ($category->category_image_path) {
            $pathWithoutAppPublic = str_replace('storage/app/public/', '', $category->category_image_path);
            Storage::disk('public')->delete($pathWithoutAppPublic);
        }

        // Eliminar la categoría
        $category->delete();

        // Devolver una respuesta JSON con un mensaje de éxito
        return response()->json(['message' => 'Category successfully removed'], 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso donde la categoría no fue encontrada
        return response()->json(['message' => 'Category not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otro error y registrar el mensaje de error
        Log::error('An error occurred while removing the category: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred while removing the category'], 500);
    }
}



}
