<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BaseRepository;

abstract class AbstractService implements BaseService
{
    public function __construct(protected readonly BaseRepository $repo)
    {
    }

    /** @param array<string, mixed> $filters */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($filters, $page, $perPage);
    }

    public function getById(int $id): ?\Siro\Core\Model
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): \Siro\Core\Model
    {
        return $this->repo->store($data);
    }

    public function update(int $id, array $data): ?\Siro\Core\Model
    {
        return $this->repo->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
