<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tag;

/**
 * Tag data access layer.
 *
 * Provides CRUD operations with pagination.
 */
final class TagRepository
{
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return Tag::query()->orderBy('id', 'DESC')->paginate($perPage, $page);
    }

    public function findById(int $id): mixed
    {
        return Tag::find($id);
    }

    public function store(array $data): mixed
    {
        return Tag::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    public function update(int $id, array $data): mixed
    {
        $item = Tag::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    public function destroy(int $id): bool
    {
        $item = Tag::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
