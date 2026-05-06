<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use Siro\Core\Lang;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Storage;

/**
 * Post controller with file upload and multi-language support.
 *
 * @package App\Controllers
 */
final class PostController
{
    public function index(Request $request): Response
    {
        $query = Post::query();
        $locale = $request->query('locale', '');
        if ($locale !== '') {
            $query->where('locale', '=', $locale);
        }
        $result = $query->orderBy('id', 'desc')
            ->paginate((int) $request->query('per_page', 20), (int) $request->query('page', 1));
        return Response::paginated($result['data'], $result['meta'], 'Posts list');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $post = Post::find($id);
        if ($post === null) {
            return Response::error(Lang::get('messages.not_found', ['resource' => 'Post']), 404);
        }
        return Response::success($post->toArray(), 'Post detail');
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|min:3|max:255',
            'body' => 'required|min:10',
            'locale' => 'required|in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $imagePath = null;
        $file = $request->file('image');
        if ($file !== null && $file->isValid()) {
            $imagePath = $file->store('posts');
        }

        $data = [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'locale' => $validated['locale'],
            'status' => $validated['status'] ?? 'draft',
            'image' => $imagePath,
        ];

        $post = Post::create($data);
        return Response::created($post->toArray(), Lang::get('messages.post_created'));
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->param('id');
        $post = Post::find($id);
        if ($post === null) {
            return Response::error('Post not found', 404);
        }

        $validated = $request->validate([
            'title' => 'min:3|max:255',
            'body' => 'min:10',
            'locale' => 'in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $post->update($validated);
        return Response::success($post->toArray(), 'Post updated');
    }

    public function delete(Request $request): Response
    {
        $id = (int) $request->param('id');
        $post = Post::find($id);
        if ($post === null) {
            return Response::error('Post not found', 404);
        }

        if ($post->image) {
            Storage::delete($post->image);
        }
        $post->delete();
        return Response::success(null, 'Post deleted');
    }
}
