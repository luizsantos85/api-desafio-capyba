<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $newUser = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
            ]);

            $token = $newUser->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $newUser,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar o usuário: ' . $e->getMessage()], 500);
        }
    }

}
