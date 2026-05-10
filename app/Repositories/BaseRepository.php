<?php

declare(strict_types=1);

namespace App\Repositories;

use Siro\Core\Model;

abstract class BaseRepository
{
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->createModel();
    }

    abstract protected function createModel(): Model;

    public function findAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $query = $this->model->query()->orderBy('id', 'DESC');
        return $query->paginate($perPage, $page);
    }

    public function findById(int $id): mixed
    {
        return $this->model->find($id);
    }

    public function store(array $data): mixed
    {
        return $this->model->create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    public function update(int $id, array $data): mixed
    {
        $item = $this->model->find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    public function destroy(int $id): bool
    {
        $item = $this->model->find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
