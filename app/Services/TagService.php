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

    public function getAll(int $page = 1, int $perPage = 20): array
    {
        return $this->repo->findAll($page, $perPage);
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
