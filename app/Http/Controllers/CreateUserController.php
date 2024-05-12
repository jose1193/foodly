<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Provider;
use App\Helpers\ImageHelper;
use App\Http\Requests\CreateUserRequest;
use Illuminate\Support\Facades\Auth;

class CreateUserController extends Controller 
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
  public function store(CreateUserRequest $request)
{
    DB::beginTransaction();

    try {
        // Validar y extraer datos.
        $data = $request->validated();
        $user = $this->createUser($data);

        // Asignar roles y otros datos sin dependencia de la subida de archivos.
        $this->assignUserRole($data, $user);
        $this->handleUserProviderData($request, $data, $user);

        // Crear token de usuario.
        $tokenData = $this->createUserToken($user);

        // Manejar la subida de la foto de perfil solo si todo lo demás ha sido exitoso.
        $this->handleUserProfilePhoto($request, $user);

        // Confirmar todas las operaciones.
        DB::commit();

        // Devolver respuesta exitosa con datos del usuario y token.
        return response()->json([
            'message' => 'User created successfully',
            'token' => $tokenData['token'],
            'token_type' => 'Bearer',
            'token_created_at' => $tokenData['created_at'],
            'user' => new UserResource($user)
        ], 200);

    } catch (\Exception $e) {
        // Revertir todos los cambios en caso de error.
        DB::rollback();
        return response()->json(['error' => $e->getMessage()], 422);
    }
}

private function handleUserProfilePhoto(CreateUserRequest $request, User $user)
{
    if ($request->hasFile('photo')) {
        $photoPath = ImageHelper::storeAndResizeProfilePhoto($request->file('photo'), 'public/profile-photos');
        // Asegurarse de que la foto solo se asigne si se ha guardado correctamente.
        if ($photoPath) {
            $user->update(['profile_photo_path' => $photoPath]);
        }
    }
}


private function createUser(array $data): User
{
    $data['uuid'] = Uuid::uuid4()->toString();
    $data['password'] = Hash::make($data['password']);
    return User::create($data);
}



private function assignUserRole(array $data, User $user)
{
    $role = Role::find($data['role_id']);
    if (!$role) {
        throw new \Exception('Invalid role ID');
    }
    $user->assignRole($role);
}

private function handleUserProviderData(CreateUserRequest $request, array $data, User $user)
{
    if (isset($data['provider_id'], $data['provider'], $data['provider_avatar'])) {
        Provider::create([
            'uuid' => Uuid::uuid4()->toString(),
            'provider_id' => $data['provider_id'],
            'provider' => $data['provider'],
            'provider_avatar' => $data['provider_avatar'],
            'user_id' => $user->id,
        ]);
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }
    } elseif ($request->has('provider_id') || $request->has('provider') || $request->has('provider_avatar')) {
        throw new \Exception('Incomplete provider data');
    }
}

private function createUserToken(User $user): array
{
    $userToken = $user->createToken('API Token User Register')->plainTextToken;
    $token = PersonalAccessToken::findToken(explode('|', $userToken)[1]);
    $formattedTokenCreatedAt = $token ? $token->created_at->format('Y-m-d H:i:s') : null;

    return ['token' => explode('|', $userToken)[1], 'created_at' => $formattedTokenCreatedAt];
}


//protected function createUser(array $input): User
//{
    //return User::create([
        //'name' => $input['name'],
       // 'last_name' => $input['last_name'],
        //'username' => $input['username'],
        //'date_of_birth' => $input['date_of_birth'],
        //'uuid' => Uuid::uuid4()->toString(),
        //'email' => $input['email'],
        //'password' => Hash::make($input['password']),
        //'phone' => $input['phone'],
        //'address' => $input['address'],
        //'zip_code' => $input['zip_code'],
        //'city' => $input['city'],
        //'country' => $input['country'],
        //'gender' => $input['gender'],
    //]);
//}
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CreateUserRequest $request)
    {
       try {
        $user = Auth::user();

        // Validar la solicitud y obtener los datos validados
        $data = $request->validated();

        // Excluir la contraseña del arreglo de datos
        unset($data['password']);

        // Actualizar el perfil del usuario
        $user->update($data);

        return response()->json(['message' => 'Profile updated successfully', 'user' => new UserResource($user)], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción ocurrida durante el proceso
        return response()->json(['error' => $e->getMessage()], 422);
    }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}