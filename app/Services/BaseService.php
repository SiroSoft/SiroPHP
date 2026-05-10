<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BaseRepository;

abstract class BaseService
{
    protected BaseRepository $repo;

    public function __construct(BaseRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @param array<string, string> $filters
     * @return array{data: array<int, mixed>, meta: array<string, mixed>}
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($filters, $page, $perPage);
    }

    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): mixed
    {
        return $this->repo->store($data);
    }

    public function update(int $id, array $data): mixed
    {
        return $this->repo->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
