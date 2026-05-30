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

    /**
     * Get paginated products with optional filters and sorting.
     *
     * Supports filtering by: category, status, price_min, price_max, search (name LIKE).
     * Supports sorting by: id, name, price, stock, created_at (asc/desc).
     *
     * @param array<array-key, mixed> $queryParams Query parameters (sort, order, category, status, price_min, price_max, search)
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
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

    /**
     * Find a product by ID. Returns null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        $result = $this->repo->findById($id);
        return $result !== null ? $result->toArray() : null;
    }

    /**
     * Create a new product with defaults for missing fields.
     * Defaults: price=0, stock=0, status='active'.
     *
     * @param array<string, mixed> $data Validated product data
     * @return \Siro\Core\Model Created product model
     */
    public function create(array $data): \Siro\Core\Model
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

    /**
     * Update a product. Returns null if not found.
     *
     * @param array<string, mixed> $data Validated product data
     * @return \Siro\Core\Model|null Updated product model, or null if not found
     */
    public function update(int $id, array $data): ?\Siro\Core\Model
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
