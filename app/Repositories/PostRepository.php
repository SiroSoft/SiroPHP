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
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = Post::query();

        if (isset($filters['locale']) && $filters['locale'] !== '') {
            $query->where('locale', '=', $filters['locale']);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage, $page);
    }

    public function findById(int $id): mixed
    {
        return Post::find($id);
    }

    public function store(array $data): mixed
    {
        return Post::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    public function update(int $id, array $data): mixed
    {
        $item = Post::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    public function destroy(int $id): bool
    {
        $item = Post::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
