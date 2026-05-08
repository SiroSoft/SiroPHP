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

    public function getAll(array $queryParams = [], int $page = 1, int $perPage = 20): array
    {
        $filters = [];
        if (isset($queryParams['status']) && $queryParams['status'] !== '') {
            $filters['status'] = $queryParams['status'];
        }

        return $this->repo->findAll($filters, $page, $perPage);
    }

    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    public function create(array $validated): mixed
    {
        $data = $validated;

        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        return $this->repo->store($data);
    }

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

    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
