<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\SocialLoginRequest;
use App\Http\Resources\SocialLoginResource;
use App\Http\Resources\UserResource;
use Ramsey\Uuid\Uuid;
use App\Actions\Fortify\CreateNewUser;


class SocialLoginController extends Controller
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

     
    public function handleProviderCallback(SocialLoginRequest $request)
{
    try {
        $validatedData = $request->validated();
         // Validar el proveedor
        $provider = $this->validateProvider($validatedData['provider']);
        if ($provider instanceof \Illuminate\Http\JsonResponse) {
            return $provider; // Retorna la respuesta de validación directamente si no es un proveedor válido
        }
        
        // Obtener los datos del usuario del proveedor a través de Socialite
        $providerUser = Socialite::driver($validatedData['provider'])->userFromToken($validatedData['access_provider_token']);

        // Validar el formato del correo electrónico
        $email = filter_var($providerUser->getEmail(), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new \Exception('Invalid email address from provider');
        }

        // Buscar al usuario por su correo electrónico
        $user = User::where('email', $email)->first();

        if ($user) {
           // Actualizar el campo email_verified_at si es necesario
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }

             // Verificar si ya existe un registro para el usuario y el proveedor
            $existingProvider = $user->providers()
                ->firstWhere('provider', $validatedData['provider']);

            // Si ya existe un registro, actualizarlo; de lo contrario, crear uno nuevo
            if ($existingProvider) {
                $existingProvider->update(['provider_avatar' => $providerUser->getAvatar()]);
            } else {
                // Crear un nuevo registro
                $user->providers()->create([
                    'uuid' => Uuid::uuid4()->toString(),
                    'provider' => $validatedData['provider'],
                    'provider_id' => $providerUser->getId(),
                    'provider_avatar' => $providerUser->getAvatar(),
                ]);
            }

            // Iniciar sesión al usuario
            Auth::login($user);

            // Crear un nuevo token de acceso para el usuario
            $token = $user->createToken('auth_token')->plainTextToken;

    $tokenCreatedAt = $user->tokens()->where('name', 'auth_token')->first()->created_at;
    $formattedTokenCreatedAt = $tokenCreatedAt->format('Y-m-d H:i:s');
            // Devolver la respuesta con los datos relevantes
            return response()->json([
                'message' => 'User logged successfully',
                'token' => explode('|', $token)[1],
                'token_type' => 'Bearer',
                'token_created_at' => $formattedTokenCreatedAt,
                'user' => new UserResource($user),
                
                 
            ], 200);
        } else {
            // Crear un array con los datos del usuario del proveedor
            $userData = [
                'provider' => $validatedData['provider'],
                'provider_id' => $providerUser->getId(),
                'provider_avatar' => $providerUser->getAvatar(),
                'name' => $providerUser->getName(),
                'username' => $providerUser->getNickname(), 
                'email' => $email,
            ];

            // Recurso del usuario (opcional)
            $userResource = new SocialLoginResource($userData);

            // Devuelve la respuesta JSON con los datos del usuario del proveedor
            return response()->json([
                'message' => 'User fetched successfully from provider',
                'user' => $userResource,
            ], 200);
        }
    } catch (\Throwable $e) {
        // Manejar la excepción
        return response()->json(['message' => 'Error occurred: ' . $e->getMessage()], 500);
    }
}





protected function validateProvider($provider)
{
    if (!in_array($provider, ['google','facebook', 'twitter'])) {
         return response()->json(["message" => 'You can only login via google, facebook, or twitter account'], 400);
    }
}

/**
     * Display the specified resource.
     */


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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
