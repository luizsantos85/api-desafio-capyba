<?php

namespace Tests\Feature\Api\Auth;

use Illuminate\Support\Facades\Password;
use Tests\Feature\Api\UtilsTraitToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use UtilsTraitToken;

    //Falha login
    public function test_fail_auth(): void
    {
        $response = $this->postJson('/auth', []);

        $response->assertStatus(400);
    }

    //Sucesso login
    public function test_auth(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/auth', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        // $response->dump(); //debugar resposta (nesse caso verifica se foi retornado um token de usuario)

        $response->assertStatus(200);
    }

    //Falha logout
    public function test_logout_unauthenticated()
    {
        $response = $this->postJson('/logout');
        $response->assertStatus(401);
    }

    //Sucesso logout
    public function test_logout()
    {
        $response = $this->postJson('/logout', [], $this->defaultHeaders());
        $response->assertStatus(200);
    }

    //Falha envio email reset de senha
    public function test_send_reset_link_fail_email_not_found()
    {
        $response = $this->postJson('/auth/password-forgot', [
            'email' => 'nonexistent@example.com',
        ]);
        $response->assertStatus(422)
            ->assertJson(['status' => 'We can\'t find a user with that email address.']);
    }

    //Sucesso envio email reset de senha
    public function test_send_reset_link_success()
    {
        $user = $this->createUser();

        $response = $this->postJson('/auth/password-forgot', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'We have emailed your password reset link.']);
    }

    //Falha no reset de senha com token
    public function test_reset_password_fail_invalid_token()
    {
        $user = $this->createUser();

        $invalidToken = 'invalid-token';

        $data = [
            'email' => $user->email,
            'password' => 'newpass123',
            'token' => $invalidToken,
        ];

        $response = $this->postJson('/auth/password-reset', $data);

        $response->assertStatus(422)
            ->assertJson(['status' => 'This password reset token is invalid.']);
    }

    //Sucesso no reset de senha com token
    public function test_reset_password_token_success()
    {
        $user = $this->createUser();
        $token = Password::createToken($user);

        $data = [
            'email' => $user->email,
            'password' => 'newpass123',
            'token' => $token,
        ];

        $response = $this->postJson('/auth/password-reset', $data);
        $response->assertStatus(200)
            ->assertJson(['status' => 'Your password has been reset.']);
    }








}
