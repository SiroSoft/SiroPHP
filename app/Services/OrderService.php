<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrderRepository;

/**
 * Order business logic layer.
 *
 * Handles items JSON encoding/decoding between API and storage.
 */
final class OrderService extends AbstractService
{
    public function __construct(OrderRepository $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Get paginated orders with optional status/user_id filter.
     *
     * @param array<array-key, mixed> $queryParams Query parameters (status, user_id)
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
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

    /**
     * Create a new order. Items array is JSON-encoded for storage.
     * Maximum 50 items per order.
     *
     * @param array<string, mixed> $validated Validated order data including 'items' array
     * @return \Siro\Core\Model Created order model
     * @throws \InvalidArgumentException If more than 50 items
     */
    public function create(array $validated): \Siro\Core\Model
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
     * @return \Siro\Core\Model|null
     */
    public function update(int $id, array $validated): ?\Siro\Core\Model
    {
        $data = $validated;
        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = json_encode($data['items']);
        }

        return $this->repo->update($id, $data);
    }
}
