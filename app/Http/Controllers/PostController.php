<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $pageSize = $request->query('page_size', 10);
        $page = $request->query('page', 1);
        $search = $request->query('search', '');

        $posts = $user->posts()
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'LIKE', "%{$search}%")
                ->orWhere('content', 'LIKE', "%{$search}%");
            })
        ->paginate($pageSize, ['*'], 'page', $page);

        if($posts->isEmpty()){
            return response()->json(['error' => 'Nenhum post encontrado.'], 400);
        }

        return response()->json(['posts' => $posts], 200);
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
