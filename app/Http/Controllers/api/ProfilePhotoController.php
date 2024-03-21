<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use App\Models\User;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;



class ProfilePhotoController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user(); // Obtén el usuario autenticado

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png'],
        ]);

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo'); // Obtén el objeto UploadedFile
            
            // Crear una instancia de Intervention Image
            $image = Image::make($photo->getRealPath());

            // Obtén el ancho y alto de la imagen original
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Verifica si el ancho o el alto son mayores que 700 para redimensionar
            if ($originalWidth > 700 || $originalHeight > 700) {
                // Calcula el factor de escala para mantener la relación de aspecto
                $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);

                // Calcula el nuevo ancho y alto para redimensionar la imagen
                $newWidth = $originalWidth * $scaleFactor;
                $newHeight = $originalHeight * $scaleFactor;

                // Redimensiona la imagen
                $image->resize($newWidth, $newHeight);
            }

            // Genera una ruta única para la nueva imagen
            $path = $photo->store('profile-photos', 'public');

          // Elimina la imagen anterior si existe
           if ($user->profile_photo_path) {
          $pathWithoutAppPublic = str_replace('app/public/', '', $user->profile_photo_path);
            Storage::disk('public')->delete($pathWithoutAppPublic);
            }


            // Guarda la imagen manipulada en la ruta
            $image->save(storage_path('app/public/' . $path));

            // Actualiza la foto de perfil del usuario con la ruta de la nueva imagen
            $user->profile_photo_path = 'app/public/' . $path;
            $user->save();
        }

        $response = [
            'user' => [
                'photo' => $user->profile_photo_url
            ],
            'message' => 'Successfully updated profile photo',
            
        ];

        // Retornar la respuesta JSON
        return response()->json($response);
    }
}
