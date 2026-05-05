<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;

/**
 * Category data access layer.
 *
 * Provides CRUD operations on Category model with
 * pagination support.
 *
 * @package App\Repositories
 */

final class CategoryRepository
{
    public function findAll(int $page = 1, int $perPage = 20): array
    {
        return Category::query()->orderBy('id', 'DESC')->paginate($perPage, $page);
    }

    public function findById(int $id): mixed
    {
        return Category::find($id);
    }

    public function store(array $data): mixed
    {
        return Category::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    public function update(int $id, array $data): mixed
    {
        $item = Category::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    public function destroy(int $id): bool
    {
        $item = Category::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
