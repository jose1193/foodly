<?php

namespace App\Http\Controllers;
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
    // Método refactorizado de login
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->firstOrFail();

        if (!Hash::check($request->password, $user->password)) {
            return $this->sendFailedLoginResponse();
        }

        $token = $this->createTokenForUser($user, $request->filled('remember'));
        $cookie = $this->createCookieForToken($token);

        return $this->sendSuccessLoginResponse($user, $token)->withCookie($cookie);
    }

    private function createTokenForUser($user, $remember = false)
    {
        $token = $user->createToken('auth_token')->plainTextToken;
        if ($remember) {
            $rememberToken = Str::random(60);
            $user->forceFill(['remember_token' => hash('sha256', $rememberToken)])->save();
        }
        return $token;
    }

    private function createCookieForToken($token)
    {
        return cookie('token', $token, 60 * 24 * 365); // 1 year
    }

    private function sendSuccessLoginResponse($user, $token)
    {
        return response()->json([
            'message' => 'User logged successfully',
            'token' => explode('|', $token)[1],
            'token_type' => 'Bearer',
            'token_created_at' => $user->tokens()->where('name', 'auth_token')->first()->created_at->format('Y-m-d H:i:s'),
            'user' => new UserResource($user),
        ], 200);
    }

    private function sendFailedLoginResponse()
    {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }




// USER LOGOUT
 // logout a user method
    public function logout(Request $request) {
    try {
        $request->user()->tokens()->delete();

        $cookie = cookie()->forget('token');

        return response()->json([
            'message' => 'Logged out successfully!'
        ])->withCookie($cookie);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while logging out.'
        ], 500);
    }
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



// UPDATE USER PASSWORD
public function updatePassword(Request $request)
{
    try {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:5','max:30', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{};:,<.>])[A-Za-z\d!@#$%^&*()\-_=+{};:,<>.]{5,}$/'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password does not match'], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    } catch (ValidationException $e) {
        return response()->json(['error' => $e->errors()], 422);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
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
        return response()->json(['error' => 'No se pudo restablecer la contraseña. Verifique el enlace o vuelva a intentarlo más tarde.'], 500);
    }
}



    // UPDATE PROFILE USER
    public function updateProfile(Request $request, UpdateUserProfileInformation $updater)
{
    // Validar la solicitud
    $validatedData = $request->validate([
        'name' => 'string|max:255',
        'email' => 'string|email|max:255|unique:users,email,' . $request->user()->id,
        // Agregar más reglas de validación según sea necesario
    ]);

    // Obtener el usuario actual
    $user = $request->user();

    // Verificar si el usuario tiene permiso para actualizar su perfil
    if ($user->id !== $request->user()->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    try {
        // Actualizar el perfil del usuario
        $updater->update($user, $validatedData);
    } catch (ValidationException $e) {
        return response()->json(['message' => $e->errors()], 422);
    } catch (\Exception $e) {
        // Manejar otras excepciones
        return response()->json(['message' => 'An error occurred while updating profile'], 500);
    }

    // Obtener el primer rol asignado al usuario actualizado
    $userRole = $user->roles->first()->name;

    // Devolver el objeto completo del usuario en la respuesta sin mostrar roles
    return response()->json(['user' => $user->makeVisible('user_role')->toArray(), 'message' => 'Profile successfully updated']);
}


// REGISTER

public function register(Request $request, CreateNewUser $creator)
    {
        $userCreate = $creator->create($request->all());

        return $userCreate;
    }

}