<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;


use App\Http\Requests\LoginRequest;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Resources\UserResource;


class AuthController extends Controller
{
public function __construct()
{
    $this->middleware('permission:Super Admin')->only(['getUsers']);
   
}


// USER LOGIN
 // login a user method
    public function login(LoginRequest $request) {
    $data = $request->validated();

    $user = User::where('email', $data['email'])->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
        return response()->json([
            'message' => 'Email or password is incorrect!'
        ], 401);
    }

    // Retrieve user roles
    $userRoles = $user->roles->pluck('name')->all();

    $token = $user->createToken('auth_token')->plainTextToken;

    $cookie = cookie('token', $token, 60 * 24); // 1 day

    // Add userRoles directly to the user object
    $user->userRoles = $userRoles;

    // Create the user resource
    $userResource = new UserResource($user);

    $userObject = $user->toArray();

    if ($request->filled('remember')) {
        $rememberToken = Str::random(60);
        $user->forceFill([
            'remember_token' => hash('sha256', $rememberToken),
        ])->save();

        $userObject['remember_token'] = $rememberToken;
    }

    $userObject['token'] = $token;
    $tokenCreatedAt = $user->tokens()->where('name', 'auth_token')->first()->created_at;
    $formattedTokenCreatedAt = $tokenCreatedAt->format('Y-m-d H:i:s');

    return response()->json([
        'user' => $userResource,
        'token' => explode('|', $token)[1],
        'token_type' => 'Bearer',
        'token_created_at' => $formattedTokenCreatedAt, 
        'message' => 'User logged successfully',
    ])->withCookie($cookie);
}




// USER LOGOUT
 // logout a user method
    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();

        $cookie = cookie()->forget('token');

        return response()->json([
            'message' => 'Logged out successfully!'
        ])->withCookie($cookie);
    }

    
public function logout2()
{
    if (auth()->user()) {
        auth()->user()->tokens()->delete();
        return Response::json(['message' => 'Successfully logged out'],200);
    } else {
        return Response::json(['message' => 'No active session found'], 401);
    }
}


// DETAILS CURRENT USER
 // get the authenticated user method
   public function user(Request $request) {
    // Get the authenticated user
    $user = $request->user();

    // Create a UserResource with basic user details
    $userResource = new UserResource($user);

    // Obtain the roles assigned to the user
    $userRoles = $user->roles->pluck('name')->all();

    // Add the roles information to the UserResource data
    $userResourceData = $userResource->toArray($request);
    $userResourceData['user_roles'] = $userRoles;

    // Return the modified UserResource data
    return response()->json($userResourceData);
}


// UPDATE USER PASSWORD
public function updatePassword(Request $request)
   {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password does not match'], 401);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }

    // RESET PASSWORD LINK
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Link de restablecimiento de contraseña enviado al correo electrónico.']);
        } else {
            return response()->json(['error' => 'No se pudo enviar el link de restablecimiento de contraseña.'], 500);
        }
    }

    //RESET PASSWORD PAGE MAIL
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Contraseña restablecida correctamente.']);
        } else {
            return response()->json(['error' => 'No se pudo restablecer la contraseña.'], 500);
        }
    }



    // UPDATE PROFILE USER
    public function updateProfile(Request $request, UpdateUserProfileInformation $updater)
{
    $user = $request->user();

    try {
        $updater->update($user, $request->all());
    } catch (ValidationException $e) {
        return response()->json(['message' => $e->errors()], 422);
    }

    
  // Obtener el usuario actualizado después de la actualización
    $updatedUser = $request->user();

    // Obtener el primer rol asignado al usuario actualizado
    $userRole = $updatedUser->roles->first()->name;

    // Agregar el nombre del rol al objeto de usuario
    $updatedUser->user_role = $userRole;

    // Remover la relación pivot para evitar mostrarla
    unset($updatedUser->roles);


    // Devolver el objeto completo del usuario en la respuesta
    return response()->json(['user' => $updatedUser, 'message' => 'Profile successfully updated']);
}

        // GET ALL USERS
     public function getUsers()
    {

        
        $users = User::all();
        return response()->json(['users' => $users], 200);
    }

// REGISTER

public function register(Request $request, CreateNewUser $creator)
    {
        $userCreate = $creator->create($request->all());

        return $userCreate;
    }

}