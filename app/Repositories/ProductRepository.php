<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;

/**
 * Product data access layer.
 *
 * Provides CRUD operations with category/status/price/
 * search filtering and sortable pagination.
 */
final class ProductRepository
{
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = Product::query();

        if (isset($filters['category']) && $filters['category'] !== '') {
            $query->where('category', '=', $filters['category']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', '=', $filters['status']);
        }

        if (isset($filters['price_min']) && $filters['price_min'] !== '') {
            $query->where('price', '>=', (float) $filters['price_min']);
        }

        if (isset($filters['price_max']) && $filters['price_max'] !== '') {
            $query->where('price', '<=', (float) $filters['price_max']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $query->where('name', 'LIKE', '%' . $filters['search'] . '%');
        }

        return $query->orderBy(
            $filters['sort'] ?? 'id',
            $filters['order'] ?? 'desc'
        )->paginate($perPage, $page);
    }

    public function findById(int $id): mixed
    {
        return Product::find($id);
    }

    public function store(array $data): mixed
    {
        return Product::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    public function update(int $id, array $data): mixed
    {
        $item = Product::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    public function destroy(int $id): bool
    {
        $item = Product::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
