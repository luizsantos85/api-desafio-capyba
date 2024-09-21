<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PostTest extends TestCase
{
    use UtilsTraitToken;

    public function test_posts_user_unauthenticated()
    {
        $response = $this->getJson('/posts');
        $response->assertStatus(401);
    }

    public function test_post_with_filters()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // Cria alguns posts para o usuário
        $post1 = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Title One',
            'content' => 'This is the content of the first post.'
        ]);
        $post2 = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Another Title',
            'content' => 'This is the content of another post.'
        ]);
        $post3 = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Title Three',
            'content' => 'Content of the third post.'
        ]);

        $response = $this->getJson('/posts?search=Test Title');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'posts.data')
            ->assertJsonFragment(['id' => $post1->id]);

        // Testa a busca por conteúdo
        $response = $this->getJson('/posts?search=another');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'posts.data')
            ->assertJsonFragment(['id' => $post2->id]);

        // Testa a ordenação por título
        $response = $this->getJson('/posts?ordering=title');
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $post1->id])
            ->assertJsonFragment(['id' => $post2->id])
            ->assertJsonFragment(['id' => $post3->id]);

        // Testa a ordenação decrescente
        $response = $this->getJson('/posts?ordering=-title');
        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $post3->id])
            ->assertJsonFragment(['id' => $post2->id])
            ->assertJsonFragment(['id' => $post1->id]);
    }

    public function test_get_posts_user()
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $token = $user2->createToken('auth_token')->plainTextToken;

        Post::factory()->count(20)->create(['user_id' => $user1->id]);
        Post::factory()->count(2)->create(['user_id' => $user2->id]);

        $response = $this->getJson('/posts', [
            "Authorization" => "Bearer {$token}"
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'posts.data');
    }

    public function test_post_create_unauthenticated()
    {
        Storage::fake('public');
        $newImage = UploadedFile::fake()->create('avatar.jpg', 100);

        $data = [
            'title' => 'Titulo do post',
            'content' => 'criando um novo post',
            'image' => $newImage,
        ];

        $response = $this->postJson('/posts/store', $data);
        $response->assertStatus(401);

        Storage::disk('public')->assertMissing('images/posts/' . $newImage->hashName());
        $this->assertDatabaseMissing('posts', [
            'title' => 'Titulo do post'
        ]);
    }

    public function test_post_create_success()
    {
        Storage::fake('public');
        $newImage = UploadedFile::fake()->create('post.jpg', 100);

        $data = [
            'title' => 'Titulo do post',
            'content' => 'criando um novo post',
            'image' => $newImage,
        ];

        $response = $this->postJson('/posts/store', $data, $this->defaultHeaders());
        $response->assertStatus(201);


        $savedImageName = Storage::disk('public')->allFiles('images/posts')[0];
        Storage::disk('public')->assertExists($savedImageName);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', ['title' => 'Titulo do post']);
    }

    public function test_get_post_show_unauthenticated()
    {
        $response = $this->getJson("/posts/show/fake_id");
        $response->assertStatus(401);
    }

    public function test_get_post_show_not_found()
    {
        $response = $this->getJson("/posts/show/fake_id", $this->defaultHeaders());
        $response->assertStatus(404);
    }

    public function test_get_post_show()
    {
        $user = $this->createUser();
        $token = $user->createToken('auth_token')->plainTextToken;
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson("/posts/show/{$post->id}", [
            "Authorization" => "Bearer {$token}"
        ]);
        $response->assertStatus(200);
    }

    public function test_post_update_unauthenticated()
    {
        $data = [
            'title' => 'Titulo do post',
            'content' => 'criando um novo post',
        ];

        $response = $this->putJson('/posts/update/fake_id', $data);
        $response->assertStatus(401);
    }

    public function test_post_update_not_found()
    {
        $data = [
            'title' => 'Titulo do post',
            'content' => 'criando um novo post',
        ];

        $response = $this->putJson('/posts/update/fake_id', $data, $this->defaultHeaders());
        $response->assertStatus(404);
    }

    public function test_update_post_without_verified_email()
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $this->actingAs($user);

        $post = Post::factory()->create(['user_id' => $user->id]);

        $newData = [
            'title' => 'Updated Title',
            'content' => 'Updated content.',
        ];

        $response = $this->putJson("/posts/update/{$post->id}", $newData);
        $response->assertStatus(403);
    }

    public function test_post_delete_unauthenticated()
    {
        $response = $this->deleteJson("/posts/delete/fake_id");
        $response->assertStatus(401);
    }

    public function test_post_delete_not_found()
    {
        $response = $this->deleteJson("/posts/delete/fake_id", [], $this->defaultHeaders());
        $response->assertStatus(404);
    }

    public function test_delete_post_without_verified_email()
    {
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        $this->actingAs($user);

        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/posts/delete/{$post->id}");
        $response->assertStatus(403);
    }

    public function test_post_delete()
    {
        $user = $this->createUser();
        $token = $user->createToken('auth_token')->plainTextToken;
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/posts/delete/{$post->id}",[] ,[
            "Authorization" => "Bearer {$token}"
        ]);

        $response->assertStatus(200);
    }
}
