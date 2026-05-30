<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Resources\PostResource;
use App\Role;
use App\Services\PostService;
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class PostController extends Controller
{
    public function __construct(private readonly PostService $service)
    {
    }

    /**
     * List all blog posts with pagination and optional locale/user_id filtering.
     *
     * Non-admin users see only their own posts.
     * Rate limited: 60 requests per minute.
     *
     * GET /api/posts?page=1&per_page=20&locale=en
     *
     * @param Request $request Incoming HTTP request with optional query params
     * @return Response Paginated list of posts
     */
    public function index(Request $request): Response
    {
        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 20)));

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }

        $params = $request->all();
        if ($currentUserRole !== Role::ADMIN) {
            $params['user_id'] = $currentUserId;
        }

        $result = $this->service->getAll($params, $page, $perPage);

        return $this->paginated(
            PostResource::collection($result['data']),
            $result['meta'],
            'Posts list'
        );
    }

    /**
     * Get a single blog post by ID.
     *
     * Non-admin users can only view their own posts.
     *
     * GET /api/posts/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Post detail (200) or error (403/404/422)
     */
    public function show(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }

        $post = $this->service->getById($id);
        if ($post === null) {
            return $this->error('Post not found', 404);
        }

        $postData = $post instanceof \Siro\Core\Model ? $post->toArray() : (array) $post;
        $postUserId = is_numeric($postData['user_id'] ?? null) ? (int) $postData['user_id'] : 0;
        if ($currentUserRole !== Role::ADMIN && $currentUserId !== $postUserId) {
            return $this->error('Forbidden', 403);
        }

        return $this->success(PostResource::make($postData), 'Post detail');
    }

    /**
     * Create a new blog post.
     *
     * Accepts title, body, locale (en/vi), status (draft/published).
     * Optionally accepts a file upload for cover image (max 5MB, jpg/png/gif/webp).
     *
     * POST /api/posts
     * Body: { title: string, body: string, locale: string, status?: string, cover_image?: string, category_id?: int, excerpt?: string }
     * Multipart: image file
     *
     * @param Request $request Incoming HTTP request with post data and optional file
     * @return Response Created post (201) or error (422)
     */
    public function store(Request $request): Response
    {
        $validated = $this->validate([
            'title' => 'required|min:3|max:255',
            'body' => 'required|min:10',
            'locale' => 'required|in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $currentUser = $request->user();
        $currentUserId = 0;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
        }
        $validated['user_id'] = $currentUserId;

        $rawBody = $request->all();
        if (isset($rawBody['cover_image'])) {
            $validated['image'] = $rawBody['cover_image'];
        }
        if (isset($rawBody['category_id'])) {
            $validated['category_id'] = (int) $rawBody['category_id'];
        }
        if (isset($rawBody['excerpt'])) {
            $validated['excerpt'] = $rawBody['excerpt'];
        }

        $file = $request->file('image');
        if ($file !== null && $file->isValid()) {
            $filePath = $file->getPathname();
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mime, $allowedMimes, true)) {
                return $this->error('Invalid file type', 422);
            }
            $maxSize = 5 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return $this->error('File too large. Maximum 5MB allowed.', 422);
            }
            $validated['image'] = $file->store('posts');
        }
        $post = $this->service->create($validated, $file);

        return $this->created(PostResource::make($post instanceof \Siro\Core\Model ? $post->toArray() : (array) $post), 'Post created');
    }

    /**
     * Update an existing blog post.
     *
     * Non-admin users can only update their own posts. Partial updates supported.
     *
     * PUT /api/posts/{id}
     * Body: { title?: string, body?: string, locale?: string, status?: string, cover_image?: string, category_id?: int, excerpt?: string }
     *
     * @param Request $request Incoming HTTP request with post updates
     * @return Response Updated post (200) or error (403/404/422)
     */
    public function update(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);

        $existing = $this->service->getById($id);
        if ($existing === null) {
            return $this->error('Post not found', 404);
        }

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }
        $existingData = $existing instanceof \Siro\Core\Model ? $existing->toArray() : (array) $existing;
        $postUserId = is_numeric($existingData['user_id'] ?? null) ? (int) $existingData['user_id'] : 0;
        if ($currentUserRole !== Role::ADMIN && $currentUserId !== $postUserId) {
            return $this->error('Forbidden', 403);
        }

        $validated = $this->validate([
            'title' => 'min:3|max:255',
            'body' => 'min:10',
            'locale' => 'in:en,vi',
            'status' => 'in:draft,published',
        ]);

        $rawBody = $request->all();
        if (isset($rawBody['cover_image'])) {
            $validated['image'] = $rawBody['cover_image'];
        }
        if (isset($rawBody['category_id'])) {
            $validated['category_id'] = (int) $rawBody['category_id'];
        }
        if (isset($rawBody['excerpt'])) {
            $validated['excerpt'] = $rawBody['excerpt'];
        }

        $post = $this->service->update($id, $validated);
        if ($post === null) {
            return $this->error('Post not found', 404);
        }

        return $this->success(PostResource::make($post), 'Post updated');
    }

    /**
     * Delete a blog post by ID.
     *
     * Also deletes the associated cover image from storage.
     * Non-admin users can only delete their own posts.
     *
     * DELETE /api/posts/{id}
     *
     * @param Request $request Incoming HTTP request with route param 'id'
     * @return Response Empty (204) or error (403/404/422)
     */
    public function delete(Request $request): Response
    {
        $rawId = $request->param('id');
        $id = (int) $rawId;
        if ($id <= 0) return $this->error('Invalid id', 422);

        $currentUser = $request->user();
        $currentUserId = 0;
        $currentUserRole = Role::USER;
        if (is_array($currentUser)) {
            $currentUserId = is_numeric($currentUser['id'] ?? null) ? (int) $currentUser['id'] : 0;
            $currentUserRole = is_string($currentUser['role'] ?? null) ? $currentUser['role'] : Role::USER;
        }
        $existing = $this->service->getById($id);
        if ($existing !== null) {
            $existingData = $existing instanceof \Siro\Core\Model ? $existing->toArray() : (array) $existing;
            $postUserId = is_numeric($existingData['user_id'] ?? null) ? (int) $existingData['user_id'] : 0;
            if ($currentUserRole !== Role::ADMIN && $currentUserId !== $postUserId) {
                return $this->error('Forbidden', 403);
            }
        }

        return $this->service->delete($id)
            ? $this->noContent()
            : $this->error('Post not found', 404);
    }
}
