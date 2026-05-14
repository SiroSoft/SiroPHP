<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;

final class CategoryService implements BaseService
{
    public function __construct(private readonly CategoryRepository $repo)
    {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($filters, $page, $perPage);
    }

    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): mixed
    {
        return $this->repo->store($data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): mixed
    {
        return $this->repo->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
