<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PostRepository;
use Siro\Core\Lang;
use Siro\Core\Storage;

/**
 * Post business logic layer.
 *
 * Handles file upload (image), locale filtering, and storage cleanup on delete.
 */
final class PostService
{
    public function __construct(private readonly PostRepository $repo)
    {
    }

    public function getAll(array $queryParams = [], int $page = 1, int $perPage = 20): array
    {
        $filters = [];
        if (isset($queryParams['locale']) && $queryParams['locale'] !== '') {
            $filters['locale'] = $queryParams['locale'];
        }

        return $this->repo->findAll($filters, $page, $perPage);
    }

    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    public function create(array $validated, mixed $uploadedFile = null): mixed
    {
        $data = [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'locale' => $validated['locale'],
            'status' => $validated['status'] ?? 'draft',
        ];

        if ($uploadedFile !== null) {
            $data['image'] = $uploadedFile->store('posts');
        }

        return $this->repo->store($data);
    }

    public function update(int $id, array $validated): ?array
    {
        $post = $this->repo->findById($id);
        if ($post === null) return null;

        $this->repo->update($id, $validated);

        $updated = $this->repo->findById($id);
        return $updated ? $updated->toArray() : null;
    }

    public function delete(int $id): bool
    {
        $post = $this->repo->findById($id);
        if ($post === null) return false;

        $postData = $post->toArray();
        if (!empty($postData['image'])) {
            Storage::delete($postData['image']);
        }

        return $this->repo->destroy($id);
    }

    public function notFoundMessage(): string
    {
        return Lang::get('messages.not_found', ['resource' => 'Post']);
    }
}
