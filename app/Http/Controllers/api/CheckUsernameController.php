<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CheckUsernameController extends Controller
{
    public function checkUsernameAvailability($username)
    {
        $user = User::where('username', $username)->first();

        if ($user) {
            
             return response()->json(['username' => 'unavailable']);
        } else {
           
            return response()->json(['username' => 'available']);
        }
    }
}
