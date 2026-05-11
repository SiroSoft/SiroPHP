<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\PostResource;
use App\Services\PostService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class PostController extends Controller
{
    public function __construct(private readonly PostService $service)
    {
    }

    public function index(Request $request): Response
    {
        $result = $this->service->getAll(
            $request->all(),
            (int) $request->query('page', 1),
            (int) $request->query('per_page', 20)
        );

        return $this->paginated(
            PostResource::collection($result['data']),
            $result['meta'],
            'Posts list'
        );
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        if ($id <= 0) return $this->error('Invalid id', 422);
        $post = $this->service->getById($id);

        if ($post === null) {
            return $this->error('Post not found', 404);
        }

        return $this->success(PostResource::make($post->toArray()), 'Post detail');
    }

    public function store(Request $request): Response
    {
        $validated = $this->validate([
            'title' => 'required|min:3|max:255',
            'body' => 'required|min:10',
            'locale' => 'required|in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $file = $request->file('image');
        $post = $this->service->create($validated, $file);

        return $this->created(PostResource::make($post->toArray()), 'Post created');
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');

        $validated = $this->validate([
            'title' => 'min:3|max:255',
            'body' => 'min:10',
            'locale' => 'in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $post = $this->service->update($id, $validated);
        if ($post === null) {
            return $this->error('Post not found', 404);
        }

        return $this->success(PostResource::make($post), 'Post updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');

        return $this->service->delete($id)
            ? $this->success(null, 'Post deleted')
            : $this->error('Post not found', 404);
    }
}
