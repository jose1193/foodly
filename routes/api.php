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
use App\Http\Controllers\Api\ProfilePhotoController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\SubcategoryController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\BusinessCoverImageController;
use App\Http\Controllers\Api\CheckUsernameController;
use App\Http\Controllers\Api\CheckEmailController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchCoverImageController;
use App\Http\Controllers\Api\BiometricAuthController;
use App\Http\Controllers\Api\PromotionController;

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

Route::get('/username-available/{username}', [CheckUsernameController::class, 'checkUsernameAvailability']);

Route::get('/email-available/{email}', [CheckEmailController::class, 'checkEmailAvailability']);

Route::get('/categories', [CategoryController::class, 'index']);

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
    Route::get('users-profile/{uuid}', [UsersController::class, 'show']); 
    Route::put('users-update/{uuid}', [UsersController::class, 'update']); 
    Route::delete('users-delete/{id}', [UsersController::class, 'destroy']); 
    Route::get('users-create', [UsersController::class, 'create']); 
    Route::get('users-list/{uuid}/edit', [UsersController::class, 'edit']); 
    Route::put('users-restore/{uuid}', [UsersController::class, 'restore']); 

    // Rutas relacionadas con permisos
    Route::get('permissions-list', [PermissionController::class, 'index']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::get('permissions/{id}', [PermissionController::class, 'show']);
    Route::put('permissions-update/{id}', [PermissionController::class, 'update']);
    Route::delete('permissions-delete/{id}', [PermissionController::class, 'destroy']);
    Route::get('permissions/create', [PermissionController::class, 'create']);
    Route::get('permissions/{id}/edit', [PermissionController::class, 'edit']);

    // Routes related to Categories
    

    Route::post('/categories-store', [CategoryController::class, 'store']);
    Route::put('/categories-update/{uuid}', [CategoryController::class, 'update']);
    Route::get('/categories/{uuid}', [CategoryController::class, 'show']);
    Route::delete('/categories-delete/{uuid}', [CategoryController::class, 'destroy']);
    Route::post('/categories-update-images/{uuid}/', [CategoryController::class, 'updateImage']);

     // Routes related to Subcategories
    Route::get('/subcategories', [SubcategoryController::class, 'index']);
    Route::post('/subcategories-store', [SubcategoryController::class, 'store']);
    Route::put('/subcategories-update/{uuid}', [SubcategoryController::class, 'update']);
    Route::get('/subcategories/{uuid}', [SubcategoryController::class, 'show']);
    Route::delete('/subcategories-delete/{uuid}', [SubcategoryController::class, 'destroy']);

    // Routes related to Business
    Route::get('/business', [BusinessController::class, 'index']);
    Route::post('/business-store', [BusinessController::class, 'store']);
    Route::put('/business-update/{uuid}', [BusinessController::class, 'update']);
    Route::get('/business/{uuid}', [BusinessController::class, 'show']);
    Route::delete('/business-delete/{uuid}', [BusinessController::class, 'destroy']);
    Route::post('/business-update-logo/{uuid}', [BusinessController::class, 'updateLogo']);
    Route::put('/business-restore/{uuid}', [BusinessController::class, 'restore']);


    // Routes related to Business Cover Images
    Route::get('/business-cover-images', [BusinessCoverImageController::class, 'index']);
    Route::post('/business-cover-images-store', [BusinessCoverImageController::class, 'store']);
    Route::get('/business-cover-images/{cover_image_uuid}', [BusinessCoverImageController::class, 'show']);
    //Route::put('/business-cover-images/{cover_image_uuid}', [BusinessCoverImageController::class, 'update']);
    Route::delete('/business-cover-images-delete/{cover_image_uuid}', [BusinessCoverImageController::class, 'destroy']);
    Route::post('/business-cover-images-update/{cover_image_uuid}', [BusinessCoverImageController::class, 'updateImage']);
    
        // Routes related to Business
    Route::get('/branch', [BranchController::class, 'index']);
    Route::post('/branch-store', [BranchController::class, 'store']);
    Route::put('/branch-update/{uuid}', [BranchController::class, 'update']);
    Route::get('/branch/{uuid}', [BranchController::class, 'show']);
    Route::post('/branch-update-logo/{uuid}', [BranchController::class, 'updateLogo']);
    Route::delete('/branch-delete/{uuid}', [BranchController::class, 'destroy']);
    Route::put('/branch-restore/{uuid}', [BranchController::class, 'restore']);


    // Routes related to Branch Cover Images
    Route::get('/branch-cover-images', [BranchCoverImageController::class, 'index']);
    Route::post('/branch-cover-images-store', [BranchCoverImageController::class, 'store']);
    Route::get('/branch-cover-images/{uuid}', [BranchCoverImageController::class, 'show']);
    Route::post('/branch-cover-images-update/{uuid}', [BranchCoverImageController::class, 'updateImage']);
    Route::delete('/branch-cover-images-delete/{uuid}', [BranchCoverImageController::class, 'destroy']);
     
   
    // Routes related to Biometric Login
    Route::post('/biometric-login', [BiometricAuthController::class, 'store']);

    // Routes related to Business Cover Images
    Route::get('/promotions', [PromotionController::class, 'index']);
    Route::post('/promotions-store', [PromotionController::class, 'store']);
    Route::put('/promotions-update/{uuid}', [PromotionController::class, 'update']);
    Route::get('/promotions/{uuid}', [PromotionController::class, 'show']);
    Route::delete('/promotions-delete/{uuid}', [PromotionController::class, 'destroy']);
    Route::put('/promotions-restore/{uuid}', [PromotionController::class, 'restore']);

});

//Route::fallback([ErrorController::class, 'notFound']);

  
