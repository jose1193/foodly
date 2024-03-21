<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Validation\Rules\Password;

use App\Http\Requests\PhotoUploadRequest;
use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\PersonalAccessToken;

use App\Http\Resources\UserResource;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */


     
     public function create(array $input): User|JsonResponse
{
    $validator = $this->validateInput($input);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = $this->createUser($input);

    $role = $this->findRole($input['role_id']);

    if (!$role) {
        return response()->json(['error' => 'Invalid role ID'], 422);
    }

    $user->assignRole($role);

    // Token creation
    $userToken = $user->createToken('API Token')->plainTextToken;
   

    // Enviar el objeto UserResource en la respuesta
    return $this->formatUserResponse(new UserResource($user), $userToken);
}


protected function validateInput(array $input): \Illuminate\Contracts\Validation\Validator
{
    return Validator::make($input, [
       'name' => ['required', 'string', 'max:40', 'regex:/^[a-zA-Z\s]+$/'],
        'last_name' => ['required', 'string', 'max:40', 'regex:/^[a-zA-Z\s]+$/'],
        'username' => ['required', 'string', 'max:30', 'unique:users', 'regex:/^[a-zA-Z0-9_]+$/'],
        'date_of_birth' => ['required', 'string', 'max:255'],
        'uuid' => ['nullable', 'string', 'max:255', 'unique:users'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
         'password' => [
                'required',
                'string',
                Password::min(5)->mixedCase()->numbers()->symbols()->uncompromised(),
                'confirmed',
            ],
        'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        'phone' => ['required', 'string', 'min:6', 'max:20'],
        'address' => ['required', 'string', 'max:255'],
        'zip_code' => ['required', 'string', 'max:20'],
        'city' => ['required', 'string', 'max:255'],
        'country' => ['required', 'string', 'max:255'],
        'gender' => ['required', 'in:male,female,other'],
        'role_id' => ['required', 'exists:roles,id'],
        
    ]);
}


 protected function findRole(int $roleId): ?Role
    {
        return Role::find($roleId);
    }
    /**
     * Create a new user.
     *
     * @param array<string, string> $input
     * @return User
     */
    protected function createUser(array $input): User
    {
        $user = User::create([
            'name' => $input['name'],
            'last_name' => $input['last_name'],
            'username' => $input['username'],
            'date_of_birth' => $input['date_of_birth'],
            'uuid' => 'ID-' . Uuid::uuid4()->toString(),
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'phone' => $input['phone'],
            'address' => $input['address'],
            'zip_code' => $input['zip_code'],
            'city' => $input['city'],
            'country' => $input['country'],
            'gender' => $input['gender'],
        ]);

       
    // Verificar si se envió una nueva foto
if (isset($input['photo'])) {
    // Obtener el archivo de la solicitud
    $photo = $input['photo'];

    // Generar un nombre único para la foto
    $randomString = Str::random(38); // Genera una cadena aleatoria de 40 caracteres
    $photoName = $user->id . '_' . $randomString . '.' . $photo->getClientOriginalExtension();

    // Crear una instancia de Intervention Image
    $image = Image::make($photo->getRealPath());

    // Obtienes el ancho y alto de la imagen original
    $originalWidth = $image->width();
    $originalHeight = $image->height();

    // Verificamos si el ancho o el alto son mayores que 700 para redimensionar
    if ($originalWidth > 700 || $originalHeight > 700) {
        // Calculamos el factor de escala para mantener la relación de aspecto
        $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);

        // Calculamos el nuevo ancho y alto para redimensionar la imagen
        $newWidth = $originalWidth * $scaleFactor;
        $newHeight = $originalHeight * $scaleFactor;

        // Redimensionamos la imagen
        $image->resize($newWidth, $newHeight);
    }

    // Almacenar la foto en el sistema de archivos
    $image->save(storage_path('app/public/profile-photos/' . $photoName));

    // Asignar el nombre de la foto al usuario
    $user->update(['profile_photo_path' => 'app/public/profile-photos/' . $photoName]);
}

        return $user;
    }

    
protected function formatUserResponse(UserResource $userResource, string $userToken): JsonResponse
{
    $token = PersonalAccessToken::findToken(explode('|', $userToken)[1]);
    $formattedTokenCreatedAt = $token ? $token->created_at->format('Y-m-d H:i:s') : null;
    return response()->json([
        'user' => $userResource,
        'token' => explode('|', $userToken)[1], 
        'token_type' => 'Bearer',
        'token_created_at' => $formattedTokenCreatedAt,
        'message' => 'User data was successfully registered',
    ]);
}


}
