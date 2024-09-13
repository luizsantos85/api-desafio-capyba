<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'image' => 'nullable|image'
        ]);

        try {
            $imageName = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $imageService = new ImageService;
                $imageName = $imageService->createOrUpdateImage($request->image);
            }

            $newUser = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => bcrypt($validatedData['password']),
                'image' => $imageName
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

    public function show()
    {
        $user = auth()->user();
        return response()->json(['user' => $user], 200);
    }

    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . auth()->user()->id,
            'image' => 'nullable|image'
        ]);

        try {
            $user = auth()->user();

            if ($request->filled('name')) {
                $user->name = $validatedData['name'];
            }

            if ($request->filled('email') && $validatedData['email'] !== $user->email) {
                $user->email = $validatedData['email'];
            }

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $imageService = new ImageService;
                $imageName = $imageService->createOrUpdateImage($request->image, $user);
                $user->image = $imageName;
            }

            $user->save();

            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar o usuário: ' . $e->getMessage()], 500);
        }
    }

}
