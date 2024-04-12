<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Resources\UserResource;

class BiometricAuthController extends Controller
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
    public function store(Request $request)
{
    try {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthenticated user'], 401);
        }

        $user = Auth::user();
        $token = $user->tokens()->where('name', 'auth_token')->first();

        if (!$token) {
            return response()->json(['error' => 'No personal session token associated with the user was found'], 401);
        }

        $plainTextToken = $token->token;
        $userResource = new UserResource($user);
        $formattedTokenCreatedAt = $token->created_at->format('Y-m-d H:i:s');

        return response()->json([
            'user' => $userResource,
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'token_created_at' => $formattedTokenCreatedAt,
            'message' => 'User logged successfully',
        ], 201);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Internal Server Error'], 500);
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
