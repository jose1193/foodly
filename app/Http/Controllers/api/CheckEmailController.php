<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CheckEmailController extends Controller
{
    public function checkEmailAvailability($email)
    {
        // Validar el formato del correo electrónico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // El formato del correo electrónico es inválido
            return response()->json(['error' => 'Invalid email format'], 400);
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            // El correo electrónico ya está en uso
            return response()->json(['email' => 'unavailable']);
        } else {
            // El correo electrónico está disponible
            return response()->json(['email' => 'available']);
        }
    }
}

