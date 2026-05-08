<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

/**
 * User data access layer.
 *
 * Provides CRUD operations with email lookup support.
 */
final class UserRepository
{
    /** Get paginated list of all users. */
    public function findAll(int $page = 1, int $perPage = 15): array
    {
        return User::query()->orderBy('id', 'DESC')->paginate($perPage, $page);
    }

    /** Find a user by ID or null if not found. */
    public function findById(int $id): mixed
    {
        return User::find($id);
    }

    /** Find a user by email or null if not found. */
    public function findByEmail(string $email): mixed
    {
        $result = User::where('email', '=', strtolower(trim($email)))->limit(1)->get();
        return $result[0] ?? null;
    }

    /** Create a new user record. */
    public function store(array $data): mixed
    {
        return User::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    /** Update a user. Returns null if not found. */
    public function update(int $id, array $data): mixed
    {
        $item = User::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    /** Delete a user. Returns true if deleted, false if not found. */
    public function destroy(int $id): bool
    {
        $item = User::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
