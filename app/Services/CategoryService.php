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
     * Get paginated list of categories.
     *
     * @param array<string, mixed> $filters Optional filtering criteria
     * @return array<string, mixed> Paginated result with 'data' and 'meta'
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($filters, $page, $perPage);
    }

    /** Find a category by ID. Returns null if not found. */
    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /** Create a new category. Returns the created model. */
    public function create(array $data): mixed
    {
        return $this->repo->store($data);
    }

    /** Update a category. Returns null if not found. */
    public function update(int $id, array $data): mixed
    {
        return $this->repo->update($id, $data);
    }

    /** Delete a category. Returns true if deleted, false if not found. */
    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
