<?php

declare(strict_types=1);

namespace App\Services;

interface BaseService
{
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    public function getById(int $id): mixed;
    public function create(array $data): mixed;
    public function update(int $id, array $data): mixed;
    public function delete(int $id): bool;
}
