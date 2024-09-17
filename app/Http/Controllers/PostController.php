<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatecreatePostRequest;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $params = $request->all();
        $user = auth()->user();

        $posts = $this->search($params, $user);

        if ($posts->isEmpty()) {
            return response()->json(['error' => 'Nenhum post encontrado.'], 404);
        }

        return response()->json(['posts' => $posts], 200);
    }

    public function store(UpdatecreatePostRequest $request)
    {
        $data = $request->all();

        try {
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $imageService = new ImageService;
                $data['image'] = $imageService->createOrUpdateImage($request->image, 'posts');
            }

            $post = $request->user()->posts()->create($data);

            return response()->json(['post' => $post], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao criar o post: ' . $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        $user = auth()->user();
        $post = Post::where('user_id', $user->id)->where('id', $id)->first();

        if (!$post) {
            return response()->json(['error' => 'Nenhum post encontrado.'], 404);
        }

        return response()->json(['post' => $post], 200);
    }

    public function update(UpdatecreatePostRequest $request, string $id)
    {
        $user = auth()->user();

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['error' => 'Você precisa verificar seu e-mail para atualizar o post.'], 403);
        }

        $post = Post::where('user_id', $user->id)->where('id', $id)->first();

        if (!$post) {
            return response()->json(['error' => 'Nenhum post encontrado.'], 404);
        }

        try {
            $data = $request->all();

            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $imageService = new ImageService;
                $data['image'] = $imageService->createOrUpdateImage($data['image'], 'posts', $post);
            }

            $post->update($data);

            return response()->json(['post' => $post], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao atualizar o post: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $user = auth()->user();

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['error' => 'Você precisa verificar seu e-mail para deletar o post.'], 403);
        }

        $post = Post::where('user_id', $user->id)->where('id', $id)->first();

        if (!$post) {
            return response()->json(['error' => 'Nenhum post encontrado.'], 404);
        }

        try {
            if ($post->image) {
                $imageService = new ImageService;
                $imageService->deleteImage('posts', $post->image);
            }

            $post->delete();

            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro ao deletar o post: ' . $e->getMessage()], 500);
        }
    }

    public function search(array $params, User $user)
    {
        $pageSize = $params['page_size'] ?? 10;
        $page = $params['page'] ?? 1;
        $params['ordering'] = $params['ordering'] ?? 'id';
        $sortDirection = $params['ordering'][0] === '-' ? 'desc' : 'asc';
        $ordering = ltrim($params['ordering'], '-');

        $posts = Post::where('user_id', $user->id)
            ->when(isset($params['search']) && !empty($params['search']), function ($query) use ($params) {
                $query->where(function ($q) use ($params) {
                    $q->where('title', 'LIKE', "%{$params['search']}%")
                        ->orWhere('content', 'LIKE', "%{$params['search']}%");
                });
            })
            ->orderBy($ordering, $sortDirection)
            ->paginate($pageSize, ['*'], 'page', $page);

        return $posts;
    }
}
