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

    /** @return array<string, mixed>|null */
    public function getByEmail(string $email): ?array
    {
        $rows = User::where('email', '=', $email)->limit(1)->get();
        if ($rows === []) {
            return null;
        }
        $data = $rows[0]->toArray();
        // password is in $hidden, include it for auth checks
        $data['password'] = $rows[0]->getAttribute('password');
        return $data;
    }

    /** @param array<string, mixed> $data */
    public function createUser(array $data): User
    {
        $name = $data['name'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        /** @var string $name */
        /** @var string $email */
        /** @var string $password */
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => self::hashPassword($password),
            'status' => 1,
            'verification_token' => hash('sha256', bin2hex(random_bytes(32))),
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
        $hashedToken = hash('sha256', $token);
        $rows = User::where('verification_token', '=', $hashedToken)->limit(1)->get();
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
            $hashedToken = hash('sha256', $resetToken);
            $user->update([
                'password_reset_token' => $hashedToken,
                'password_reset_expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);
        }
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $hashedToken = hash('sha256', $token);
        $rows = User::where('password_reset_token', '=', $hashedToken)->limit(1)->get();
        $user = $rows[0] ?? null;
        if ($user === null) {
            return false;
        }
        $userData = $user->toArray();
        $expiresAt = $userData['password_reset_expires_at'] ?? '';
        $tokenVersion = $userData['token_version'] ?? 1;
        /** @var string $expiresAt */
        /** @var int|string $tokenVersion */
        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            return false;
        }
        $passwordHash = self::hashPassword($newPassword);
        $user->update([
            'password' => $passwordHash,
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
            'token_version' => (int) $tokenVersion + 1,
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
        $rawEmail = $data['email'] ?? '';
        /** @var string $rawEmail */
        $email = strtolower(trim($rawEmail));

        $existing = $this->repo->findByEmail($email);
        if ($existing !== null) {
            throw new DuplicateEmailException($email);
        }

        $rawPassword = $data['password'] ?? '';
        /** @var string $rawPassword */
        $userData = [
            'name' => $data['name'],
            'email' => $email,
            'password' => self::hashPassword($rawPassword),
            'status' => 1,
        ];

        $user = $this->repo->store($userData);
        /** @var \Siro\Core\Model|null $user */
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
            $rawEmail = $data['email'];
            /** @var string $rawEmail */
            $email = strtolower(trim($rawEmail));
            $existing = $this->repo->findByEmail($email);
            /** @var \Siro\Core\Model|null $existing */
            if ($existing !== null) {
                $existingId = $existing->toArray()['id'] ?? 0;
                /** @var int|string $existingId */
                if ((int) $existingId !== $id) {
                    throw new DuplicateEmailException($email);
                }
            }
            $updateData['email'] = $email;
        }

        if (isset($data['password'])) {
            $rawPassword = $data['password'];
            /** @var string $rawPassword */
            $updateData['password'] = self::hashPassword($rawPassword);
        }

        if ($updateData === []) {
            throw new NoFieldsToUpdateException();
        }

        $this->repo->update($id, $updateData);

        $updated = $this->repo->findById($id);
        /** @var \Siro\Core\Model|null $updated */
        return $updated ? $updated->toArray() : null;
    }

    public function incrementLoginAttempts(int $userId, int $currentAttempts): void
    {
        $newAttempts = $currentAttempts + 1;
        $update = ['login_attempts' => $newAttempts];

        if ($newAttempts >= 5) {
            $update['locked_until'] = date('Y-m-d H:i:s', time() + 900);
        }

        User::where('id', '=', $userId)->update($update);
    }

    public function resetLoginAttempts(int $userId): void
    {
        User::where('id', '=', $userId)->update([
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    private static function hashPassword(string $password): string
    {
        return password_hash($password, \PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function delete(int $id): bool
    {
        return $this->repo->destroy($id);
    }
}
