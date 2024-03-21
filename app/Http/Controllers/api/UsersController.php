<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Hash;
use Illuminate\Support\Arr;
use App\Actions\Jetstream\DeleteUser;

class UsersController extends Controller
{

    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

}

    
    // SHOW LIST OF USERS
    public function index(Request $request)
    {
        $data = User::orderBy('id', 'DESC')->get();
        return response()->json(['data' => $data], 200);
    }


    // SYNC ROLES
    public function create()
    {
        $roles = Role::orderBy('id', 'DESC')->get();
        return response()->json(['roles' => $roles], 200);
    }

    // STORE USER
    public function store(Request $request)
{
    try {
        $this->validateUser($request);

        $input = $request->all();

        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'username' => ['required', 'unique:users,username'],
            // Agrega otras reglas de validación según sea necesario
        ]);

        $input['password'] = Hash::make($input['password']);

        $user = User::create($input);
        $this->syncRoles($user, $request->input('roles'));

        return response()->json(['message' => 'User created successfully'], 200);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    }
}



    // UPDATE USER
    public function update(Request $request, $id)
    {
        
        try {
            $this->validateUser($request, $id);

            $user = User::find($id);
            $input = $request->all();

            $this->updatePassword($user, $input);

            $user->update($input);
            $this->syncRoles($user, $request->input('roles'));

            return response()->json(['message' => 'User updated successfully'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }


    // FIELDS VALIDATION RULES
    private function validateUser(Request $request, $id = null)
    {
    $rules = [
        'name' => 'required',
        'email' => 'required|email|unique:users,email,' . $id,
        'phone' => 'required|min:6|max:20',
        'address' => 'required|min:3|max:255',
        'zip_code' => 'required|min:3|max:255',
        'city' => 'required|min:3|max:255',
        'country' => 'required|min:3|max:255',
        'gender' => 'required|min:3|max:255',
        'role_id' => 'required',
    ];

    // Añadir reglas de validación para el password solo en ciertos casos
    if ($id === null || $request->filled('password')) {
        $rules['password'] = [
            'sometimes',
            'nullable',
            'regex:/^\S+$/',
            'min:4',
            'max:20',
        ];
    }

         $this->validate($request, $rules);
    }


    // SYNC ROLES
    private function syncRoles(User $user, $roles)
    {
        $user->roles()->sync($roles);
    }


    // UPDATE PASSWORD USER
    private function updatePassword(User $user, array &$input)
    {
        if (!empty($input['password']) && $input['password'] != $user->password) {
            $input['password'] = bcrypt($input['password']);
        } else {
            $input['password'] = $user->password;
        }
    }

    // SHOW PROFILE USER
    public function show($uuid)
    {
        $user = User::where('uuid', $uuid)->first();
        return response()->json(['user' => $user], $user ? 200 : 404);
    }



    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();

        return response()->json(['user' => $user, 'roles' => $roles, 'userRole' => $userRole], $user ? 200 : 404);
    }


    // USER LOGOUT
    public function destroy($id, DeleteUser $deleteUser)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $deleteUser->delete($user);

        return response()->json(['message' => 'User deleted successfully'], 200);
    }



}
