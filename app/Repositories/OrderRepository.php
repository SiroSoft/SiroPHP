<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;

/**
 * Order data access layer.
 *
 * Provides CRUD operations with optional status filtering.
 */
final class OrderRepository
{
    /** Get paginated orders with optional status filter. */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = Order::query();

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'DESC')->paginate($perPage, $page);
    }

    /** Find an order by ID or null if not found. */
    public function findById(int $id): mixed
    {
        return Order::find($id);
    }

    /** Create a new order record. */
    public function store(array $data): mixed
    {
        return Order::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    /** Update an order. Returns null if not found. */
    public function update(int $id, array $data): mixed
    {
        $item = Order::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    /** Delete an order. Returns true if deleted, false if not found. */
    public function destroy(int $id): bool
    {
        $item = Order::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
