<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

final class UserService
{
    public function __construct(private readonly UserRepository $repo)
    {
    }

    public function getAll(int $page = 1, int $perPage = 15): array
    {
        return $this->repo->findAll($page, $perPage);
    }

    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): array
    {
        $email = strtolower(trim($data['email']));

        $existing = $this->repo->findByEmail($email);
        if ($existing !== null) {
            throw new \RuntimeException('Email has already been taken');
        }

        $passwordHash = password_hash($data['password'], \PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new \RuntimeException('Unable to hash password');
        }

        $userData = [
            'name' => $data['name'],
            'email' => $email,
            'password' => $passwordHash,
            'status' => 1,
        ];

        $user = $this->repo->store($userData);
        return $user ? $user->toArray() : [];
    }

    public function update(int $id, array $data): ?array
    {
        $user = $this->repo->findById($id);
        if ($user === null) return null;

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $email = strtolower(trim($data['email']));
            $existing = $this->repo->findByEmail($email);
            if ($existing !== null && (int) $existing->toArray()['id'] !== $id) {
                throw new \RuntimeException('Email has already been taken');
            }
            $updateData['email'] = $email;
        }

        if (isset($data['password'])) {
            $passwordHash = password_hash($data['password'], \PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new \RuntimeException('Unable to hash password');
            }
            $updateData['password'] = $passwordHash;
        }

        if ($updateData === []) {
            throw new \RuntimeException('No fields to update');
        }

        $this->repo->update($id, $updateData);

        $updated = $this->repo->findById($id);
        return $updated ? $updated->toArray() : null;
    }

    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
