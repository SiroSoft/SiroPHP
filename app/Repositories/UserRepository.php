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
    public function findAll(int $page = 1, int $perPage = 15): array
    {
        return User::query()->orderBy('id', 'DESC')->paginate($perPage, $page);
    }

    public function findById(int $id): mixed
    {
        return User::find($id);
    }

    public function findByEmail(string $email): mixed
    {
        $result = User::where('email', '=', strtolower(trim($email)))->limit(1)->get();
        return $result[0] ?? null;
    }

    public function store(array $data): mixed
    {
        return User::create($data + ['created_at' => date('Y-m-d H:i:s')]);
    }

    public function update(int $id, array $data): mixed
    {
        $item = User::find($id);
        if ($item === null) return null;
        $item->update($data);
        return $item;
    }

    public function destroy(int $id): bool
    {
        $item = User::find($id);
        if ($item === null) return false;
        return (bool) $item->delete();
    }
}
