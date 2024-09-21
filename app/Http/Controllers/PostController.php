<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatecreatePostRequest;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/posts",
     *     summary="Lista os posts do usuário autenticado",
     *     description="Retorna uma lista de posts do usuário autenticado com base nos parâmetros de busca.",
     *     tags={"Posts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Filtro de busca por título ou conteúdo",
     *         required=false,
     *         @OA\Schema(type="string", example="")
     *     ),
     *     @OA\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="Quantidade de posts por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página a ser retornada",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="ordering",
     *         in="query",
     *         description="Campo de ordenação (exemplo: '-id' para decrescente ou 'id' para crescente)",
     *         required=false,
     *         @OA\Schema(type="string", example="-id")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de posts",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="posts",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Post")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhum post encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Nenhum post encontrado.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/posts/store",
     *     summary="Cria um novo post com upload de imagem",
     *     description="Cria um novo post e faz upload de uma imagem, utilizando multipart/form-data.",
     *     tags={"Posts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "content"},
     *                 @OA\Property(property="title", type="string", description="Título do post"),
     *                 @OA\Property(property="content", type="string", description="Conteúdo do post"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Imagem do post")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Post criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="post", ref="#/components/schemas/Post")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao criar o Post"
     *     )
     * )
     */
    public function store(UpdatecreatePostRequest $request)
    {
        $data = $request->validated();

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

    /**
     * @OA\Get(
     *     path="/posts/show/{id}",
     *     summary="Exibe o post selecionado",
     *     description="Retorna o post selecionado.",
     *     tags={"Posts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID do post a ser visualizado"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="",
     *         @OA\JsonContent(
     *             @OA\Property(property="post", ref="#/components/schemas/Post")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhum post encontrado."
     *     )
     * )
     */
    public function show(string $id)
    {
        $user = auth()->user();
        $post = Post::where('user_id', $user->id)->where('id', $id)->first();

        if (!$post) {
            return response()->json(['error' => 'Nenhum post encontrado.'], 404);
        }

        return response()->json(['post' => $post], 200);
    }

    /**
     * @OA\Post(
     *     path="/posts/update/{id}",
     *     summary="Atualiza um post existente",
     *     description="Atualiza um post e permite o upload de uma nova imagem. Apenas usuários com e-mail verificado podem realizar essa ação.",
     *     tags={"Posts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID do post a ser atualizado"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="title", type="string", description="Título do post"),
     *                 @OA\Property(property="content", type="string", description="Conteúdo do post"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Nova imagem do post", nullable=true),
     *                 @OA\Property(property="_method", type="string", description="Método PUT (method spoofing)", example="PUT")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="post", ref="#/components/schemas/Post")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Usuário não verificado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post não encontrado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao atualizar o Post"
     *     )
     * )
     */
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
            $data = $request->validated();

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

    /**
     * @OA\Delete(
     *     path="/posts/delete/{id}",
     *     summary="Deleta um post existente",
     *     description="Deleta um post. Apenas usuários com e-mail verificado podem realizar essa ação.",
     *     tags={"Posts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID do post a ser deletado"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Post deletado com sucesso",
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Usuário não verificado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Post não encontrado"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro ao deletar o Post"
     *     )
     * )
     */
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
