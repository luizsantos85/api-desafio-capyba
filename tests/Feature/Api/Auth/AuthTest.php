<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\Api\UtilsTraitToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use UtilsTraitToken;

    public function test_fail_auth(): void
    {
        $response = $this->postJson('/auth', []);

        $response->assertStatus(400);
    }

    public function test_auth(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/auth', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->dump(); //debugar resposta (nesse caso verifica se foi retornado um token de usuario)

        $response->assertStatus(200);
    }

    public function test_fail_logout()
    {
        $response = $this->postJson('/logout');
        $response->assertStatus(401);
    }

    public function test_logout()
    {
        $response = $this->postJson('/logout', [], $this->defaultHeaders());
        $response->assertStatus(200);
    }
}
