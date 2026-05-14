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
        $rawPage = $request->query('page', 1);
        $rawPerPage = $request->query('per_page', 20);
        /** @var int|string $rawPage */
        /** @var int|string $rawPerPage */
        $result = $this->service->getAll(
            $request->all(),
            (int) $rawPage,
            (int) $rawPerPage
        );
        /** @var array{data: array<int, array<string, mixed>>, meta: array{page: int, per_page: int, total: int, last_page: int}} $result */

        return $this->paginated(
            PostResource::collection($result['data']),
            $result['meta'],
            'Posts list'
        );
    }

    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);

        $currentUser = $request->user();
        $currentUserId = is_array($currentUser) ? (int) ($currentUser['id'] ?? 0) : 0;
        $currentUserRole = is_array($currentUser) ? (string) ($currentUser['role'] ?? 'user') : 'user';

        $post = $this->service->getById($id);
        /** @var \Siro\Core\Model|null $post */
        if ($post === null) {
            return $this->error('Post not found', 404);
        }

        $postData = $post->toArray();
        $postUserId = (int) ($postData['user_id'] ?? 0);
        if ($currentUserId !== $postUserId && $currentUserRole !== 'admin') {
            return $this->error('Forbidden', 403);
        }

        return $this->success(PostResource::make($postData), 'Post detail');
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

        /** @var \Siro\Core\Model $post */
        return $this->created(PostResource::make($post->toArray()), 'Post created');
    }

    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;

        $currentUser = $request->user();
        $currentUserId = is_array($currentUser) ? (int) ($currentUser['id'] ?? 0) : 0;
        $currentUserRole = is_array($currentUser) ? (string) ($currentUser['role'] ?? 'user') : 'user';
        $existing = $this->service->getById($id);
        if ($existing !== null) {
            $existingData = $existing instanceof \Siro\Core\Model ? $existing->toArray() : $existing;
            $postUserId = (int) ($existingData['user_id'] ?? 0);
            if ($currentUserId !== $postUserId && $currentUserRole !== 'admin') {
                return $this->error('Forbidden', 403);
            }
        }

        $validated = $this->validate([
            'title' => 'min:3|max:255',
            'body' => 'min:10',
            'locale' => 'in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $post = $this->service->update($id, $validated);
        /** @var array<string, mixed>|null $post */
        if ($post === null) {
            return $this->error('Post not found', 404);
        }

        return $this->success(PostResource::make($post), 'Post updated');
    }

    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        /** @var int|string $rawId */
        $id = (int) $rawId;

        $currentUser = $request->user();
        $currentUserId = is_array($currentUser) ? (int) ($currentUser['id'] ?? 0) : 0;
        $currentUserRole = is_array($currentUser) ? (string) ($currentUser['role'] ?? 'user') : 'user';
        $existing = $this->service->getById($id);
        if ($existing !== null) {
            $existingData = $existing instanceof \Siro\Core\Model ? $existing->toArray() : $existing;
            $postUserId = (int) ($existingData['user_id'] ?? 0);
            if ($currentUserId !== $postUserId && $currentUserRole !== 'admin') {
                return $this->error('Forbidden', 403);
            }
        }

        return $this->service->delete($id)
            ? $this->success(null, 'Post deleted')
            : $this->error('Post not found', 404);
    }
}
