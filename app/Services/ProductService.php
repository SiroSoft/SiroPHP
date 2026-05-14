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

    /** Get paginated products with optional filters and sorting.
     * @param array<string, mixed> $queryParams
     * @return array<string, mixed>
     */
    public function getAll(array $queryParams = [], int $page = 1, int $perPage = 20): array
    {
        $sort = $queryParams['sort'] ?? 'id';
        /** @var string $sort */
        if (!in_array($sort, self::ALLOWED_SORTS, true)) {
            $sort = 'id';
        }

        $rawOrder = $queryParams['order'] ?? 'desc';
        /** @var string $rawOrder */
        $order = strtolower($rawOrder);
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }

        $category = $queryParams['category'] ?? '';
        $productStatus = $queryParams['status'] ?? '';
        $priceMin = $queryParams['price_min'] ?? '';
        $priceMax = $queryParams['price_max'] ?? '';
        $search = $queryParams['search'] ?? '';
        /** @var string $category */
        /** @var string $productStatus */
        /** @var string $priceMin */
        /** @var string $priceMax */
        /** @var string $search */
        $filters = [
            'category' => $category,
            'status' => $productStatus,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'search' => $search,
            'sort' => $sort,
            'order' => $order,
        ];

        return $this->repo->findAll($filters, $page, $perPage);
    }

    /** Find a product by ID or null if not found. */
    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /** Create a new product with defaults for missing fields.
     * @param array<string, mixed> $data
     */
    public function create(array $data): mixed
    {
        $rawPrice = $data['price'] ?? 0;
        $rawStock = $data['stock'] ?? 0;
        /** @var int|float|string $rawPrice */
        /** @var int|float|string $rawStock */
        $data['price'] = (float) $rawPrice;
        $data['stock'] = (int) $rawStock;
        $data['status'] = $data['status'] ?? 'active';

        return $this->repo->store($data);
    }

    /** Update a product. Returns null if not found.
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): mixed
    {
        if (isset($data['price'])) {
            $rawPrice = $data['price'];
            /** @var int|float|string $rawPrice */
            $data['price'] = (float) $rawPrice;
        }
        if (isset($data['stock'])) {
            $rawStock = $data['stock'];
            /** @var int|float|string $rawStock */
            $data['stock'] = (int) $rawStock;
        }

        return $this->repo->update($id, $data);
    }

    /** Delete a product. Returns true if deleted, false if not found. */
    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
