<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TagRepository;

final class TagService implements BaseService
{
    public function __construct(private readonly TagRepository $repo)
    {
    }

    /**
     * Get paginated list of tags.
     *
     * @param array<string, mixed> $filters Optional filtering criteria
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($filters, $page, $perPage);
    }

    /** Find a tag by ID. Returns null if not found. */
    public function getById(int $id): ?array
    {
        $result = $this->repo->findById($id);
        return $result !== null ? $result->toArray() : null;
    }

    /** Create a new tag. Returns the created model. */
    public function create(array $data): array
    {
        return $this->repo->store($data)->toArray();
    }

    /** Update a tag. Returns null if not found. */
    public function update(int $id, array $data): ?array
    {
        $result = $this->repo->update($id, $data);
        return $result !== null ? $result->toArray() : null;
    }

    /** Delete a tag. Returns true if deleted, false if not found. */
    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
