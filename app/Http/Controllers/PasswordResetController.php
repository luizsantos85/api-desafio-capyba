<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/password-forgot",
     *     summary="Envia o link de redefinição de senha",
     *     description="Envia um link de redefinição de senha para o e-mail do usuário.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Link de redefinição enviado com sucesso"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao enviar o link"
     *     )
     * )
     */

    //Envia link para troca de senha.
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => __($status)])
            : response()->json(['status' => __($status)], 422);
    }

    /**
     * @OA\Post(
     *     path="/auth/password-reset",
     *     summary="Redefine a senha do usuário",
     *     description="Redefine a senha do usuário utilizando o token de redefinição.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token","email","password"},
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Senha redefinida com sucesso"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao redefinir a senha"
     *     )
     * )
     */

    //Reset de senha por email
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:4', 'max:10'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? response()->json(['status' => __($status)])
            : response()->json(['status' => __($status)], 422);
    }

    /**
     * @OA\Post(
     *     path="/user/new-password",
     *     summary="Atualiza a senha do usuario",
     *     description="Atualiza a senha do usuario.",
     *     tags={"Users"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password", "newPassword"},
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="newPassword", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Senha atualizada com sucesso"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro ao atualizar a senha"
     *     )
     * )
     */
    //Nova senha atraves da api e ou site.
    public function newPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'min:4', 'max:10'],
            'newPassword' => ['required', 'min:4', 'max:10'],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Senha atual incorreta.'], 400);
        }

        if (Hash::check($request->newPassword, $user->password)) {
            return response()->json(['error' => 'A nova senha não pode ser igual à senha atual.'], 400);
        }

        $user->password = bcrypt($request->newPassword);
        $user->setRememberToken(Str::random(60));
        $user->save();

        event(new PasswordReset($user));

        return response()->json(['status' => 'Senha atualizada com sucesso.']);
    }
}
