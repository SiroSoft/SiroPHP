<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;

/**
 * Category business logic layer.
 *
 * Delegates data access to CategoryRepository and applies
 * business rules if needed.
 */
final class CategoryService
{
    public function __construct(private readonly CategoryRepository $repo)
    {
    }

    /** Get paginated list of all categories. */
    public function getAll(int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($page, $perPage);
    }

    /** Find a category by ID or null if not found. */
    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /** Create a new category with the given data. */
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
