<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\User;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\PhotoUploadRequest;


class ProfilePhotoController extends Controller
{
    public function update(Request $request)
    {
        try {
            // Validar el archivo de foto recibido en la solicitud
            $request->validate([
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048', // Validación de la imagen
            ]);

            $user = $request->user(); // Obtén el usuario autenticado

            $image = $request->file('photo'); // Obtener el archivo de imagen validado
            if ($image) {
                // Eliminar la imagen anterior si existe
                if ($user->profile_photo_path) {
                    $this->deleteImage($user->profile_photo_path);
                }

                // Guardar la nueva imagen y obtener la ruta
                $photoPath = ImageHelper::storeAndResizeProfilePhoto($image, 'public/profile-photos');
                $user->update(['profile_photo_path' => $photoPath]);
            }

            return response()->json([
                'user' => ['photo' => $user->profile_photo_url ],
                'message' => 'Successfully updated profile photo',
            ]);
        } catch (\Exception $e) {
            // Registrar el error
            Log::error('Error updating profile photo: ' . $e->getMessage());
            
            // Devolver una respuesta de error
            return response()->json([
                'error' => 'Failed to update profile photo',
                'message' => $e->getMessage()
            ], 500); // Código de estado HTTP para errores internos del servidor
        }
    }

    private function deleteImage($imagePath)
    {
        $pathWithoutAppPublic = str_replace('app/public/', '', $imagePath);
        Storage::disk('public')->delete($pathWithoutAppPublic);
    }

    
    }

