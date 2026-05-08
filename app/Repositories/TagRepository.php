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
    /** Get paginated list of all tags. */
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return Tag::query()->orderBy('id', 'DESC')->paginate($perPage, $page);
    }

    /** Find a tag by ID or null if not found. */
    public function findById(int $id): mixed
    {
        return Tag::find($id);
    }

    /** Create a new tag record. */
    public function store(array $data): mixed
    {
        return Tag::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    /** Update a tag. Returns null if not found. */
    public function update(int $id, array $data): mixed
    {
        $item = Tag::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    /** Delete a tag. Returns true if deleted, false if not found. */
    public function destroy(int $id): bool
    {
        $item = Tag::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
