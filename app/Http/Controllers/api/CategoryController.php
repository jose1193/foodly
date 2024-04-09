<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    $validatedData = $request->validated();

    // Generar un UUID
      $validatedData['user_id'] = Auth::id();
    $validatedData['category_uuid'] = Uuid::uuid4()->toString();

    // Crear la categoría
    $category = Category::create($validatedData);

    // Guardar la imagen si está presente
    $this->storeCategoryImage($request, $category);

    // Respuesta
    return $category
        ? response()->json(['message' => 'Category created successfully', 'categories' => new CategoryResource($category)], 201)
        : response()->json(['message' => 'Error creating category'], 500);
}



private function storeCategoryImage($request, $category)
{
    if ($request->hasFile('category_image_path')) {
        $image = $request->file('category_image_path');
        $imageName = $category->category_uuid . '.' . $image->getClientOriginalExtension();
        
        // Guardar la imagen redimensionada
        $this->resizeImage($image, storage_path('app/public/category_images/' . $imageName));

        // Asignar la ruta de la imagen al modelo de categoría
        $category->category_image_path = 'storage/app/public/category_images/' . $imageName;
        $category->save();
    }
}



private function resizeImage($image, $path)
{
    // Crear una instancia de Intervention Image
    $img = Image::make($image->getRealPath());

    // Obtener el ancho y alto de la imagen original
    $originalWidth = $img->width();
    $originalHeight = $img->height();

    // Verificar si se necesita redimensionar
    if ($originalWidth > 700 || $originalHeight > 700) {
        // Calcular el factor de escala para mantener la relación de aspecto
        $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);

        // Calcular el nuevo ancho y alto para redimensionar la imagen
        $newWidth = $originalWidth * $scaleFactor;
        $newHeight = $originalHeight * $scaleFactor;

        // Redimensionar la imagen
        $img->resize($newWidth, $newHeight);
    }

    // Guardar la imagen en el sistema de archivos
    $img->save($path);
}


public function updateImage(UpdateCategoryImageRequest $request, $uuid)
{
    try {
        $category = Category::where('category_uuid', $uuid)->firstOrFail();

        // Guardar la imagen
        if ($request->hasFile('category_image_path')) {
            // Obtener el archivo de imagen
            $image = $request->file('category_image_path');

            // Eliminar la imagen anterior si existe
            if ($category->category_image_path) {
                $pathWithoutAppPublic = str_replace('storage/app/public/', '', $category->category_image_path);
                Storage::disk('public')->delete($pathWithoutAppPublic);
            }

            // Guardar la nueva imagen
            $photoPath = $image->store('public/category_images');

            // Redimensionar la imagen
            $this->resizeImage($image, storage_path('app/' . $photoPath));

            // Actualizar la ruta de la imagen en el modelo Category
            $category->category_image_path = 'storage/app/' . $photoPath;
            $category->save();
        }

        // Devolver el recurso actualizado
        return new CategoryResource($category);
    } catch (\Exception $e) {
        // Manejar el error
        return response()->json(['error' => 'Error updating category image'], 500);
    }
}




   public function show($uuid)
{
    // Encontrar la categoría por su ID
     $category = Category::where('category_uuid', $uuid)->first();

    // Devolver una respuesta JSON con la categoría encontrada o un mensaje de error si no se encuentra
    return $category ? 
        response()->json(['categories' => new CategoryResource($category)]) :
        response()->json(['message' => 'Category not found'], 404);
}





  public function update(CategoryRequest $request, $uuid)
{
    // Encontrar la categoría por su ID
    $category = Category::where('category_uuid', $uuid)->first();

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



public function destroy($uuid)
{
    // Encontrar la categoría por su UUID
     $category = Category::where('category_uuid', $uuid)->first();

    // Verificar si se encontró la categoría
    if (!$category) {
        return response()->json(['message' => 'Category not found'], 404);
    }

    // Eliminar las imágenes asociadas si existen
    if ($category->category_image_path) {
        $pathWithoutAppPublic = str_replace('storage/app/public/', '', $category->category_image_path);
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }

    // Eliminar la categoría
    $category->delete();

    // Devolver una respuesta JSON con un mensaje de éxito
    return response()->json(['message' => 'Category successfully removed'], 200);
}



}
