<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TagRepository;

/**
 * Tag business logic layer.
 *
 * Delegates CRUD operations to TagRepository.
 */
final class TagService
{
    public function __construct(private readonly TagRepository $repo)
    {
    }

    /** Get paginated list of all tags. */
    public function getAll(int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($page, $perPage);
    }

    /** Find a tag by ID or null if not found. */
    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /** Create a new tag. */
    public function create(array $data): mixed
    {
        return $this->repo->store($data);
    }

    /** Update a tag. Returns null if not found. */
    public function update(int $id, array $data): mixed
    {
        return $this->repo->update($id, $data);
    }

    /** Delete a tag. Returns true if deleted, false if not found. */
    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
