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
        // Validar la solicitud y obtener los datos validados
        $data = $request->validated();

        // Agregar uuid y hashear la contraseña
        $data['uuid'] = Uuid::uuid4()->toString();
        $data['password'] = Hash::make($data['password']);

        // Crear el usuario con los datos validados
        $user = User::create($data);
        

        // Verificar si se envió una nueva foto de perfil
        if ($request->hasFile('photo')) {
            // Obtener el archivo de la solicitud
            $image = $request->file('photo');
            $photoPath = ImageHelper::storeAndResize($image, 'public/profile-photos');

            // Asignar el nombre de la foto al usuario
            $user->update(['profile_photo_path' => $photoPath]);
        }

        // Obtener el rol asociado al ID proporcionado
        $role = Role::find($data['role_id']);
        if (!$role) {
            throw new \Exception('Invalid role ID');
        }

        // Asignar el rol al usuario
        $user->assignRole($role);

        // Verificar y guardar los datos del proveedor si existen
        if (isset($data['provider_id'], $data['provider'], $data['provider_avatar'])) {
            // Crear el proveedor asociado al usuario
            Provider::create([
                'uuid' => Uuid::uuid4()->toString(),
                'provider_id' => $data['provider_id'],
                'provider' => $data['provider'],
                'provider_avatar' => $data['provider_avatar'],
                'user_id' => $user->id,
            ]);

            // Actualizar el campo email_verified_at si es necesario
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }
        } elseif ($request->has('provider_id') || $request->has('provider') || $request->has('provider_avatar')) {
            throw new \Exception('Incomplete provider data');
        }

        DB::commit();

        // Devolver una respuesta adecuada
        return response()->json(['message' => 'User created successfully', 'user' => new UserResource($user)], 201);
    } catch (\Exception $e) {
        // Manejar cualquier excepción ocurrida durante el proceso
        DB::rollback();
        return response()->json(['error' => $e->getMessage()], 422);
    }
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