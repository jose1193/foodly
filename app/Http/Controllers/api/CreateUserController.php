<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        // Obtener el ID del usuario actualmente autenticado
        $data['uuid'] = Uuid::uuid4()->toString();
        $data['password'] = Hash::make($data['password']);
        // Verificar si se envió una nueva foto
        if (isset($data['photo'])) {
            // Obtener el archivo de la solicitud
            $image = $data['photo'];
            $photoPath = ImageHelper::storeAndResize($image, 'public/profile-photos');

            if ($photoPath) {
                // Asignar el nombre de la foto al usuario
                $data['profile_photo_path'] = $photoPath;
            } else {
                throw new \Exception('Failed to upload profile photo');
            }
        }

        // Crear el usuario con los datos validados
        $user = User::create($data);

        // Obtener el rol asociado al ID proporcionado
        $role = Role::find($data['role_id']);
        if (!$role) {
            throw new \Exception('Invalid role ID');
        }

        // Asignar el rol al usuario
        $user->assignRole($role);
        // Token creation
        //$userToken = $user->createToken('API Token')->plainTextToken;
       
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
        return response()->json(['message' => 'User created successfully', 
        'user' => new UserResource($user)], 201);
    } catch (\Exception $e) {
        // Manejar cualquier excepción ocurrida durante el proceso
        DB::rollback();
        return response()->json(['error' => $e->getMessage()], 422);
    }
}



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
