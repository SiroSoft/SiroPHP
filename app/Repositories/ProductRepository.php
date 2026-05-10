<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use Siro\Core\Model;

final class ProductRepository extends BaseRepository
{
    protected function createModel(): Model
    {
        return new Product();
    }

    /**
     * @param array<string, string> $filters
     * @return array{data: array<int, mixed>, meta: array<string, mixed>}
     */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = $this->model->query();

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
            $search = str_replace(['%', '_'], ['\%', '\_'], $filters['search']);
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        return $query->orderBy(
            $filters['sort'] ?? 'id',
            $filters['order'] ?? 'desc'
        )->paginate($perPage, $page);
    }
}
