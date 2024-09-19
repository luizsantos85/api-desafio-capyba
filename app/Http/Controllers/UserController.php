<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageService;
use App\Http\Requests\UpdateCreateUserRequest;

class UserController extends Controller
{
    /**
     * @OA\Post(
     *     path="/create-user",
     *     summary="Cria um novo usuário",
     *     description="Cria um novo usuário e retorna o token de autenticação",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","email","password"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="password", type="string"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Imagem do")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao criar o usuário"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/user/profile",
     *     summary="Exibe o usuário autenticado",
     *     description="Retorna os detalhes do usuário autenticado.",
     *     tags={"Users"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Usuário autenticado",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     )
     * )
     */
    public function show()
    {
        $user = auth()->user();
        return response()->json(['user' => $user], 200);
    }

    /**
     * @OA\Post(
     *     path="/user/update",
     *     summary="Atualiza os dados do usuário autenticado",
     *     description="Atualiza os dados do usuário autenticado.",
     *     tags={"Users"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email"},
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Nova imagem", nullable=true),
     *                 @OA\Property(property="_method", type="string", description="Método PUT (method spoofing)", example="PUT")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário atualizado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/user/verify/{id}/{hash}",
     *     summary="Verifica o email do usuário",
     *     description="Verifica o email do usuário usando o ID e o hash de verificação.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         description="Hash de verificação do email",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verificado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email verificado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Verificação falhou",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Verificação falhou")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Usuário não encontrado")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/user/{id}/verify/resend",
     *     summary="Reenvia o e-mail de verificação",
     *     description="Reenvia o link de verificação de e-mail se o e-mail do usuário ainda não estiver verificado.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Link de verificação enviado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Link de verificação enviado para o seu e-mail.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="E-mail já verificado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Seu e-mail já está verificado.")
     *         )
     *     )
     * )
     */
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
