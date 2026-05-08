<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;

/**
 * Post data access layer.
 *
 * Provides CRUD operations with optional locale filtering.
 */
final class PostRepository
{
    /** Get paginated posts with optional locale filter. */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = Post::query();

        if (isset($filters['locale']) && $filters['locale'] !== '') {
            $query->where('locale', '=', $filters['locale']);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage, $page);
    }

    /** Find a post by ID or null if not found. */
    public function findById(int $id): mixed
    {
        return Post::find($id);
    }

    /** Create a new post record. */
    public function store(array $data): mixed
    {
        return Post::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    /** Update a post. Returns null if not found. */
    public function update(int $id, array $data): mixed
    {
        $item = Post::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    /** Delete a post. Returns true if deleted, false if not found. */
    public function destroy(int $id): bool
    {
        $item = Post::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
