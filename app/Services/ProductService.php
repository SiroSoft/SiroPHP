<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;

/**
 * Product business logic layer.
 *
 * Provides category/status/price/search filtering with
 * configurable sorting and pagination.
 */
final class ProductService
{
    private const ALLOWED_SORTS = ['id', 'name', 'price', 'stock', 'created_at'];

    public function __construct(private readonly ProductRepository $repo)
    {
    }

    public function getAll(array $queryParams = [], int $page = 1, int $perPage = 20): array
    {
        $sort = $queryParams['sort'] ?? 'id';
        if (!in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'id';
        }

        $order = strtolower($queryParams['order'] ?? 'desc');
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }

        $filters = [
            'category' => $queryParams['category'] ?? '',
            'status' => $queryParams['status'] ?? '',
            'price_min' => $queryParams['price_min'] ?? '',
            'price_max' => $queryParams['price_max'] ?? '',
            'search' => $queryParams['search'] ?? '',
            'sort' => $sort,
            'order' => $order,
        ];

        return $this->repo->findAll($filters, $page, $perPage);
    }

    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): mixed
    {
        $data['price'] = (float) ($data['price'] ?? 0);
        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['status'] = $data['status'] ?? 'active';

        return $this->repo->store($data);
    }

    public function update(int $id, array $data): mixed
    {
        if (isset($data['price'])) {
            $data['price'] = (float) $data['price'];
        }
        if (isset($data['stock'])) {
            $data['stock'] = (int) $data['stock'];
        }

        return $this->repo->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
