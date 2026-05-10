<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DuplicateEmailException;
use App\Exceptions\NoFieldsToUpdateException;
use App\Models\User;
use App\Repositories\UserRepository;

final class UserService
{
    public function __construct(private readonly UserRepository $repo)
    {
    }

    public static function incrementTokenVersion(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $user = User::find($userId);
        if ($user === null) {
            return false;
        }

        return $user->update(['token_version' => ($user->token_version ?? 0) + 1]) > 0;
    }

    /** @return array<string, mixed> */
    public function getAll(int $page = 1, int $perPage = 15): array
    {
        return $this->repo->findAll([], $page, $perPage);
    }

    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws DuplicateEmailException
     */
    public function create(array $data): array
    {
        $email = strtolower(trim($data['email']));

        $existing = $this->repo->findByEmail($email);
        if ($existing !== null) {
            throw new DuplicateEmailException($email);
        }

        $passwordHash = password_hash($data['password'], \PASSWORD_DEFAULT);

        $userData = [
            'name' => $data['name'],
            'email' => $email,
            'password' => $passwordHash,
            'status' => 1,
        ];

        $user = $this->repo->store($userData);
        return $user ? $user->toArray() : [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     * @throws DuplicateEmailException
     * @throws NoFieldsToUpdateException
     */
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
                throw new DuplicateEmailException($email);
            }
            $updateData['email'] = $email;
        }

        if (isset($data['password'])) {
            $passwordHash = password_hash($data['password'], \PASSWORD_DEFAULT);
            $updateData['password'] = $passwordHash;
        }

        if ($updateData === []) {
            throw new NoFieldsToUpdateException();
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
