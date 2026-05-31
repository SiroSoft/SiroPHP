<?php

declare(strict_types=1);

namespace App\Services;

interface BaseService
{
    /**
     * Get paginated list of resources with optional filters.
     *
     * @param array<array-key, mixed> $filters Key-value pairs for filtering results
     * @param int $page Page number (1-indexed)
     * @param int $perPage Items per page (max 100)
     * @return array{data: \Siro\Core\Model[], meta: array{page: int, per_page: int, total: int, last_page: int}}
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array;

    /**
     * Find a single resource by ID. Returns null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array;

    /**
     * Create a new resource from validated data. Returns the created resource.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array;

    /**
     * Update an existing resource. Returns null if not found.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int $id, array $data): ?array;

    /** Delete a resource. Returns true if deleted, false if not found. */
    public function delete(int $id): bool;
}
