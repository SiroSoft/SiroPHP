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
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($filters, $page, $perPage);
    }

    /** Find a category by ID. Returns null if not found. */
    public function getById(int $id): ?array
    {
        $result = $this->repo->findById($id);
        return $result !== null ? $result->toArray() : null;
    }

    /** Create a new category. Returns the created model. */
    public function create(array $data): array
    {
        return $this->repo->store($data)->toArray();
    }

    /** Update a category. Returns null if not found. */
    public function update(int $id, array $data): ?array
    {
        $result = $this->repo->update($id, $data);
        return $result !== null ? $result->toArray() : null;
    }

    /** Delete a category. Returns true if deleted, false if not found. */
    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
