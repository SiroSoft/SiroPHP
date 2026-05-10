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
     * @param array<string, string> $filters
     * @return array{data: array<int, mixed>, meta: array<string, mixed>}
     */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = $this->model->query();
        if (isset($filters['locale']) && $filters['locale'] !== '') {
            $query->where('locale', '=', $filters['locale']);
        }
        return $query->orderBy('id', 'desc')->paginate($perPage, $page);
    }
}
