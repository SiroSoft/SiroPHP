<?php

declare(strict_types=1);

namespace App\Services;

interface BaseService
{
    /**
     * Get paginated list of resources with optional filters.
     *
     * @param array<string, mixed> $filters Key-value pairs for filtering results
     * @param int $page Page number (1-indexed)
     * @param int $perPage Items per page (max 100)
     * @return array<string, mixed> Contains 'data' and 'meta' keys
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 20): array;

    /** Find a single resource by ID. Returns null if not found. */
    public function getById(int $id): mixed;

    /** Create a new resource from validated data. Returns the created resource. */
    public function create(array $data): mixed;

    /** Update an existing resource. Returns null if not found. */
    public function update(int $id, array $data): mixed;

    /** Delete a resource. Returns true if deleted, false if not found. */
    public function delete(int $id): bool;
}
