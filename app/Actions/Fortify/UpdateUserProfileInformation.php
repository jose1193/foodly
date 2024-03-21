<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'username' => ['required', 'max:255', Rule::unique('users')->ignore($user->id)],
            'date_of_birth' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:51200'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'max:255'],
            
        ])->validateWithBag('updateProfileInformation');
        $this->updateProfilePhoto($user, $input);
        

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'last_name' => $input['last_name'],
                'username' => $input['username'],
                'date_of_birth' => $input['date_of_birth'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'address' => $input['address'],
                'zip_code' => $input['zip_code'],
                'city' => $input['city'],
                'country' => $input['country'],
                'gender' => $input['gender'],
                
            ])->save();
        }
    }

    
private function updateProfilePhoto(User $user, array $input): void
{
    if (isset($input['photo'])) {
        $photo = $input['photo'];

       
            
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
}

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }
}
