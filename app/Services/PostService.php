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

    /** Get paginated posts with optional locale filter.
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    public function getAll(array $queryParams = [], int $page = 1, int $perPage = 20): array
    {
        $filters = [];
        if (isset($queryParams['locale']) && $queryParams['locale'] !== '') {
            $locale = $queryParams['locale'];
            /** @var string $locale */
            $filters['locale'] = $locale;
        }

        return $this->repo->findAll($filters, $page, $perPage);
    }

    /** Find a post by ID or null if not found. */
    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /** Create a new post with optional image upload.
     * @param array<string, mixed> $validated
     */
    public function create(array $validated, mixed $uploadedFile = null): mixed
    {
        $data = [
            'title' => $validated['title'],
            'body' => $validated['body'],
            'locale' => $validated['locale'],
            'status' => $validated['status'] ?? 'draft',
        ];

        if ($uploadedFile !== null) {
            /** @var \Siro\Core\UploadedFile $uploadedFile */
            $data['image'] = $uploadedFile->store('posts');
        }

        return $this->repo->store($data);
    }

    /** Update a post. Returns null if not found.
     * @param array<string, mixed> $validated
     * @return array<string, mixed>|null
     */
    public function update(int $id, array $validated): ?array
    {
        $result = $this->repo->update($id, $validated);
        /** @var \Siro\Core\Model|null $result */
        if ($result === null) return null;

        return $result->toArray();
    }

    /** Delete a post and its associated image. Returns true if deleted. */
    public function delete(int $id): bool
    {
        $post = $this->repo->findById($id);
        /** @var \Siro\Core\Model|null $post */
        if ($post === null) return false;

        $postData = $post->toArray();
        $image = $postData['image'] ?? '';
        /** @var string $image */
        if ($image !== '') {
            Storage::delete($image);
        }

        return $this->repo->destroy($id);
    }

    /** Get localized "not found" message. */
    public function notFoundMessage(): string
    {
        return Lang::get('messages.not_found', ['resource' => 'Post']);
    }
}
