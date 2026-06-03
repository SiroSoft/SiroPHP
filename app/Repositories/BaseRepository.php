<?php

declare(strict_types=1);

namespace App\Repositories;

use Siro\Core\Model;

abstract class BaseRepository
{
    protected Model $model;

    /** @var array<int, string> */
    protected array $allowedFilters = ['status', 'user_id'];

    public function __construct()
    {
        $this->model = $this->createModel();
    }

    abstract protected function createModel(): Model;

    /**
     * @param array<string, mixed> $filters
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = $this->model->query()->orderBy('id', 'DESC');
        foreach ($filters as $key => $value) {
            if (!in_array($key, $this->allowedFilters, true)) {
                continue;
            }
            $query = $query->where($key, $value);
        }
        return $query->paginate($perPage, $page);
    }

    public function findById(int $id): ?Model
    {
        return $this->model->find($id);
    }

    /** @param array<string, mixed> $data */
    public function store(array $data): Model
    {
        return $this->model->create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): ?Model
    {
        $item = $this->model->find($id);
        if ($item === null) {
            return null;
        }
        $item->update($data);
        return $item;
    }

    public function destroy(int $id): bool
    {
        $item = $this->model->find($id);
        if ($item === null) {
            return false;
        }
        return (bool) $item->delete();
    }

    public function count(): int
    {
        $result = \Siro\Core\Database::select("SELECT COUNT(*) as count FROM {$this->model->getTable()}");
        $count = isset($result[0]['count']) && is_numeric($result[0]['count']) ? $result[0]['count'] : 0;
        return (int) $count;
    }
}
