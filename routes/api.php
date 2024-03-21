<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Actions\Fortify\CreateNewUser;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\PermissionController;
//use App\Http\Controllers\Api\ErrorController;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Api\ProfilePhotoController ;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


///------------- ROUTE GOOGLE AUTH ---------///
Route::get('/google-auth/redirect', function () {
    return Socialite::driver('google')->redirect();
});
 


Route::get('/google-auth/callback', function () {
    $googleUser = Socialite::driver('google')->user();
 
    $user = User::updateOrCreate([
        'google_id' => $googleUser->id,
    ], [
        'name' => $googleUser->name,
        'email' => $googleUser->email,
         'email_verified_at' => now(),
       
    ]);
 
 
     // Accede al token del usuario autenticado
        $token = $googleUser->token;
     
        $tokenData = $user->createToken('API Token')->plainTextToken;


    Auth::login($user);
 
    return response()->json([
            'message' => 'Authentication successful',
            'user' => $user,
            'token' => $token,
            'token_data' => $tokenData
        ]);
});


///------------- END ROUTE GOOGLE AUTH ---------///

Route::post('login', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Rutas protegidas por autenticación y verificación
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::get('/users', [AuthController::class, 'getUsers']);
    Route::post('update-password', [AuthController::class, 'updatePassword']);
    
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('update-profile', [AuthController::class, 'updateProfile']);

    
    Route::post('update-profile-photo', [ProfilePhotoController::class, 'update']);


    // Rutas relacionadas con roles
    Route::get('roles-list', [RoleController::class, 'index']); // Obtener una lista de roles
    Route::post('roles', [RoleController::class, 'store']); // Crear un nuevo rol
    Route::get('roles/{id}', [RoleController::class, 'show']); // Mostrar un rol específico
    Route::put('roles-update/{id}', [RoleController::class, 'update']); // Actualizar un rol existente
    Route::delete('roles-delete/{id}', [RoleController::class, 'destroy']); // Eliminar un rol existente
    Route::get('roles-permissions', [RoleController::class, 'create']); // Mostrar listado de permisos
    Route::get('roles/{id}/edit', [RoleController::class, 'edit']); // Mostrar listado de roles y permisos del usuario a editar

    // Rutas relacionadas con usuarios
    Route::get('users-list', [UsersController::class, 'index']); 
    Route::post('users-store', [UsersController::class, 'store']); 
    Route::get('users-id/{id}', [UsersController::class, 'show']); 
    Route::put('users-update/{id}', [UsersController::class, 'update']); 
    Route::delete('users-delete/{id}', [UsersController::class, 'destroy']); 
    Route::get('users-create', [UsersController::class, 'create']); 
    Route::get('users-list/{id}/edit', [UsersController::class, 'edit']); 

    // Rutas relacionadas con permisos
    Route::get('permissions-list', [PermissionController::class, 'index']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::get('permissions/{id}', [PermissionController::class, 'show']);
    Route::put('permissions-update/{id}', [PermissionController::class, 'update']);
    Route::delete('permissions-delete/{id}', [PermissionController::class, 'destroy']);
    Route::get('permissions/create', [PermissionController::class, 'create']);
    Route::get('permissions/{id}/edit', [PermissionController::class, 'edit']);
    
});

//Route::fallback([ErrorController::class, 'notFound']);

  
