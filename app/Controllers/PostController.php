<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PostService;
use Siro\Core\Request;
use Siro\Core\Response;

/**
 * Post CRUD controller with file upload and multi-language support.
 *
 * Demonstrates file upload via PostService and i18n error messages.
 */
final class PostController
{
    public function __construct(private readonly PostService $service)
    {
    }

    /** List posts with optional locale filter and pagination. */
    public function index(Request $request): Response
    {
        $result = $this->service->getAll(
            $request->all(),
            (int) $request->query('page', 1),
            (int) $request->query('per_page', 20)
        );

        return Response::paginated($result['data'], $result['meta'], 'Posts list');
    }

    /** Get a single post by ID. */
    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $post = $this->service->getById($id);

        if ($post === null) {
            return Response::error($this->service->notFoundMessage(), 404);
        }

        return Response::success($post->toArray(), 'Post detail');
    }

    /** Create a new post with optional image upload. */
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|min:3|max:255',
            'body' => 'required|min:10',
            'locale' => 'required|in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $file = $request->file('image');
        $post = $this->service->create($validated, $file);

        return Response::created($post->toArray(), 'Post created');
    }

    /** Update post fields. */
    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');

        $validated = $request->validate([
            'title' => 'min:3|max:255',
            'body' => 'min:10',
            'locale' => 'in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $post = $this->service->update($id, $validated);
        if ($post === null) {
            return Response::error('Post not found', 404);
        }

        return Response::success($post, 'Post updated');
    }

    /** Delete a post and its associated image file. */
    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');

        return $this->service->delete($id)
            ? Response::success(null, 'Post deleted')
            : Response::error('Post not found', 404);
    }
}
