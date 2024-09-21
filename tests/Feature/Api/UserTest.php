<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserTest extends TestCase
{
    use UtilsTraitToken;

    //Falha criando o usuario
    public function test_user_create_required_email()
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->create('avatar.jpg', 100);

        // Dados incompletos - ausência do campo 'email'
        $data = [
            'name' => 'Luiz Santos',
            'password' => 'password123',
            'image' => $image,
        ];

        $response = $this->postJson('/create-user', $data);
        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['email']);

        Storage::disk('public')->assertMissing('images/user/' . $image->hashName());

        $this->assertDatabaseMissing('users', [
            'name' => 'Luiz Santos',
        ]);
    }

    public function test_user_create_sucess()
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->create('avatar.jpg', 100);

        $data = [
            'name' => 'Luiz Santos',
            'email' => 'luiz@teste.com.br',
            'password' => bcrypt('password123'),
            'image' => $image,
        ];

        $response = $this->postJson('/create-user', $data);

        $savedImageName = Storage::disk('public')->allFiles('images/user')[0];
        Storage::disk('public')->assertExists($savedImageName);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'luiz@teste.com.br']);
    }

    public function test_get_profile_unauthenticated()
    {
        $response = $this->getJson('/user/profile');
        $response->assertStatus(401);
    }

    public function test_get_profile()
    {
        $response = $this->getJson('/user/profile', $this->defaultHeaders());
        $response->assertStatus(200);
    }

    public function test_user_update_unauthenticated()
    {
        Storage::fake('public');
        $newImage = UploadedFile::fake()->create('avatar.jpg', 100);

        $newData = [
            'name' => 'New Name',
            'image' => $newImage,
        ];

        $response = $this->putJson('/user/update', $newData);
        $response->assertStatus(401);

        Storage::disk('public')->assertMissing('images/user/' . $newImage->hashName());
        $this->assertDatabaseMissing('users', [
            'name' => 'New Name',
        ]);
    }

    //Sucesso no update de usuario
    public function test_user_update()
    {
        Storage::fake('public');
        $newImage = UploadedFile::fake()->create('avatar.jpg', 100);

        $newData = [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
            'password' => 'newpassword123',
            'image' => $newImage,
        ];

        $response = $this->putJson('/user/update', $newData, $this->defaultHeaders());

        $response->assertStatus(200);

        $savedImageName = Storage::disk('public')->allFiles('images/user')[0];
        Storage::disk('public')->assertExists($savedImageName);

        $this->assertDatabaseHas('users', [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);

        Storage::disk('public')->assertMissing('images/user/old_image.jpg');
    }

    //Falha de verificação de usuário
    public function test_email_verification_invalid_hash()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $invalidHash = sha1('invalid-email@example.com');

        $response = $this->getJson("/user/verify/{$user->id}/{$invalidHash}");

        $response->assertStatus(400);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    //Sucesso na verificação de usuario
    public function test_email_verification_success()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $hash = sha1($user->getEmailForVerification());
        $response = $this->getJson("/user/verify/{$user->id}/{$hash}");

        $response->assertStatus(200);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    //Falha reenvio de email de verificação
    public function test_resend_verification_email_already_verification()
    {
        $user = $this->createUser();
        $response = $this->postJson("/user/{$user->id}/verify/resend/", [] , $this->defaultHeaders());

        $response->assertStatus(400);
    }

    public function test_resend_verification_email_success()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->postJson("/user/{$user->id}/verify/resend/", [] , $this->defaultHeaders());
        $response->assertStatus(200)
            ->assertJson(['message' => 'Link de verificação enviado para o seu e-mail.']);
    }

    //Falha na atualização de nova senha
    public function test_new_password_incorrect_current_password()
    {
        $data = [
            'password' => 'password1',
            'newPassword' => 'newpass123',
        ];

        $response = $this->postJson('/user/new-password', $data, $this->defaultHeaders());
        $response->assertStatus(400);
    }

    public function test_new_password_success()
    {
        $user = $this->createUser();

        $data = [
            'password' => 'password',
            'newPassword' => 'newpass123',
        ];

        $response = $this->postJson('/user/new-password', $data, $this->defaultHeaders());
        $response->assertStatus(200);
    }










}
