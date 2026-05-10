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

    /** Get paginated orders with optional status filter.
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    public function getAll(array $queryParams = [], int $page = 1, int $perPage = 20): array
    {
        $filters = [];
        if (isset($queryParams['status']) && $queryParams['status'] !== '') {
            $filters['status'] = $queryParams['status'];
        }

        return $this->repo->findAll($filters, $page, $perPage);
    }

    /** Find an order by ID or null if not found. */
    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /** Create a new order. Items array is JSON-encoded for storage.
     * @param array<string, mixed> $validated
     */
    public function create(array $validated): mixed
    {
        $data = $validated;

        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        return $this->repo->store($data);
    }

    /** Update an order. Returns null if not found.
     * @param array<string, mixed> $validated
     */
    public function update(int $id, array $validated): mixed
    {
        $order = $this->repo->findById($id);
        if ($order === null) return null;

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
