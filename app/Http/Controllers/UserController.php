<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageService;
use App\Http\Requests\UpdateCreateUserRequest;

class UserController extends Controller
{
    public function create(UpdateCreateUserRequest $request)
    {
        $data = $request->validated();

        try {
            $imageName = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $imageService = new ImageService;
                $imageName = $imageService->createOrUpdateImage($request->image,'user');
            }

            $newUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
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

    public function update(UpdateCreateUserRequest $request)
    {
        $data = $request->validated();
        $user = auth()->user();

        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $imageService = new ImageService;
                $data['image'] = $imageService->createOrUpdateImage($data['image'],'user', $user);
            }

            $user->update($data);

            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar o usuário: ' . $e->getMessage()], 500);
        }
    }

}
