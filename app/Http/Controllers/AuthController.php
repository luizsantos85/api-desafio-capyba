<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth",
     *     summary="Loga o usuário",
     *     description="Loga o usuário e retorna o token de autenticação",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário logado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="E-mail e ou senha inválidos."
     *     )
     * )
    */
    public function auth(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)){
            return response()->json(['error' => 'E-mail e ou senha inválidos.'], 400);
        }

        $user->tokens()->delete(); //apaga os tokens de outros dispositivos caso o usuario esteja usando, (login unico)

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     summary="Desloga o usuário autenticado",
     *     description="Desloga o usuário autenticado",
     *     tags={"Auth"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sucesso"
     *     )
     * )
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json(['success' => true], 200);
    }

}
