<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;

/**
 * Order business logic layer.
 *
 * Handles items JSON encoding/decoding between API and storage.
 */
final class OrderService
{
    public function __construct(private readonly OrderRepository $repo)
    {
    }

    /**
     * Get paginated orders with optional status/user_id filter.
     *
     * @param array<string, mixed> $queryParams Query parameters (status, user_id)
     * @return array<string, mixed> Paginated result with 'data' and 'meta'
     */
    public function getAll(array $queryParams = [], int $page = 1, int $perPage = 20): array
    {
        $filters = [];
        if (isset($queryParams['status']) && $queryParams['status'] !== '') {
            $status = $queryParams['status'];
            /** @var string $status */
            $filters['status'] = $status;
        }
        $uid = $queryParams['user_id'] ?? 0;
        if (is_numeric($uid) && (int) $uid > 0) {
            $filters['user_id'] = (int) $uid;
        }

        return $this->repo->findAll($filters, $page, $perPage);
    }

    /** Find an order by ID. Returns null if not found. */
    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /**
     * Create a new order. Items array is JSON-encoded for storage.
     * Maximum 50 items per order.
     *
     * @param array<string, mixed> $validated Validated order data including 'items' array
     * @return mixed Created order model
     * @throws \InvalidArgumentException If more than 50 items
     */
    public function create(array $validated): mixed
    {
        $data = $validated;

        if (isset($data['items']) && is_array($data['items'])) {
            if (count($data['items']) > 50) {
                throw new \InvalidArgumentException('Max 50 items per order');
            }
            $data['items'] = json_encode($data['items']);
        }

        return $this->repo->store($data);
    }

    /**
     * Update an order. Returns null if not found.
     *
     * @param array<string, mixed> $validated Validated order data
     */
    public function update(int $id, array $validated): mixed
    {
        $data = $validated;
        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        return $this->repo->update($id, $data);
    }

    /** Delete an order. Returns true if deleted, false if not found. */
    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
