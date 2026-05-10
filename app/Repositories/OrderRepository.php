<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Order;
use Siro\Core\Model;

final class OrderRepository extends BaseRepository
{
    protected function createModel(): Model
    {
        return new Order();
    }

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = $this->model->query();
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', '=', $filters['status']);
        }
        return $query->orderBy('created_at', 'DESC')->paginate($perPage, $page);
    }
}
