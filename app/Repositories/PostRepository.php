<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;
use Siro\Core\Model;

final class PostRepository extends BaseRepository
{
    protected function createModel(): Model
    {
        return new Post();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = $this->model->query();
        if (isset($filters['locale']) && $filters['locale'] !== '') {
            $query->where('locale', '=', $filters['locale']);
        }
        $userId = $filters['user_id'] ?? 0;
        if (is_numeric($userId) && (int) $userId > 0) {
            $query->where('user_id', '=', (int) $userId);
        }
        return $query->orderBy('id', 'desc')->paginate($perPage, $page);
    }
}
