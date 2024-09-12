<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    //Envia link para troca de senha.
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => __($status)])
            : response()->json(['status' => __($status)], 422);
    }

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
