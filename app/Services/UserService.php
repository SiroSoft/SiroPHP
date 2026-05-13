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

    public function incrementTokenVersion(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $user = User::find($userId);
        if ($user === null) {
            return false;
        }

        return $user->update(['token_version' => ($user->token_version ?? 0) + 1]);
    }

    public function getByEmail(string $email): ?array
    {
        $rows = User::where('email', '=', $email)->limit(1)->get();
        if ($rows === []) {
            return null;
        }
        return $rows[0]->toArray();
    }

    public function createUser(array $data): User
    {
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $passwordHash,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getTokenVersion(int $userId): int
    {
        $user = User::find($userId);
        $rawVersion = $user !== null ? (int) ($user->token_version ?? 0) : 0;
        return $rawVersion > 0 ? $rawVersion : 1;
    }

    public function verifyEmail(string $token): bool
    {
        $rows = User::where('verification_token', '=', $token)->limit(1)->get();
        $user = $rows[0] ?? null;
        if ($user === null) {
            return false;
        }
        $user->update([
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null,
        ]);
        return true;
    }

    public function initiatePasswordReset(string $email): void
    {
        $rows = User::where('email', '=', $email)->limit(1)->get();
        $user = $rows[0] ?? null;
        if ($user !== null) {
            $resetToken = bin2hex(random_bytes(32));
            $user->update([
                'password_reset_token' => $resetToken,
                'password_reset_expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);
        }
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $rows = User::where('password_reset_token', '=', $token)->limit(1)->get();
        $user = $rows[0] ?? null;
        if ($user === null) {
            return false;
        }
        $userData = $user->toArray();
        $expiresAt = (string) ($userData['password_reset_expires_at'] ?? '');
        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            return false;
        }
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $user->update([
            'password' => $passwordHash,
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
            'token_version' => ($userData['token_version'] ?? 1) + 1,
        ]);
        return true;
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
