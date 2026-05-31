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
     * @param array<string, mixed> $filters
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
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

        $priceMin = $filters['price_min'] ?? '';
        $priceMax = $filters['price_max'] ?? '';
        $search = $filters['search'] ?? '';
        $sort = $filters['sort'] ?? 'id';
        $order = $filters['order'] ?? 'desc';

        if (is_string($priceMin) && $priceMin !== '') {
            $query->where('price', '>=', (float) $priceMin);
        }

        if (is_string($priceMax) && $priceMax !== '') {
            $query->where('price', '<=', (float) $priceMax);
        }

        if (is_string($search) && $search !== '') {
            $safeSearch = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where('name', 'LIKE', '%' . $safeSearch . '%');
        }

        return $query->orderBy(
            is_string($sort) ? $sort : 'id',
            is_string($order) ? $order : 'desc'
        )->paginate($perPage, $page);
    }
}
