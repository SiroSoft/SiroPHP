<?php

declare(strict_types=1);

namespace App\Services;

interface BaseService
{
    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array;
    public function getById(int $id): mixed;
    /** @param array<string, mixed> $data */
    public function create(array $data): mixed;
    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): mixed;
    public function delete(int $id): bool;
}
