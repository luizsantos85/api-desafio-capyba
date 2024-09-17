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
                $imageName = $imageService->createOrUpdateImage($request->image, 'user');
            }

            $newUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'image' => $imageName
            ]);

            $token = $newUser->createToken('auth_token')->plainTextToken;
            $newUser->sendEmailVerificationNotification();

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
                $data['image'] = $imageService->createOrUpdateImage($data['image'], 'user', $user);
            }

            $user->update($data);

            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar o usuário: ' . $e->getMessage()], 500);
        }
    }

    public function verifyEmailUser(string $id, string $hash)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        if (hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
                return response()->json(['message' => 'Email verificado com sucesso'], 200);
            } else {
                return response()->json(['message' => 'Email já verificado'], 200);
            }
        }

        return response()->json(['error' => 'Verificação falhou'], 400);
    }

    public function resendVerificationEmail(string $id)
    {
        $user = User::find($id);

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Seu e-mail já está verificado.'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link de verificação enviado para o seu e-mail.'], 200);
    }
}
