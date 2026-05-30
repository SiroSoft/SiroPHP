<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\UserCreatedEvent;
use App\Exceptions\DuplicateEmailException;
use App\Exceptions\NoFieldsToUpdateException;
use App\Models\User;
use App\Role;
use App\Repositories\RefreshTokenRepository;
use App\Repositories\UserRepository;

final class UserService
{
    public function __construct(
        private readonly UserRepository $repo,
        private readonly RefreshTokenRepository $refreshTokenRepo,
    ) {
    }

    public function incrementTokenVersion(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $user = $this->repo->findById($userId);
        if ($user === null) {
            return false;
        }

        $rawVersion = $user['token_version'] ?? 0;
        $currentVersion = is_numeric($rawVersion) ? (int) $rawVersion : 0;
        $this->repo->updateWhere('id', $userId, ['token_version' => $currentVersion + 1]);
        return true;
    }

    /** @return array<string, mixed>|null */
    public function getByEmail(string $email): ?array
    {
        return $this->repo->findByEmail($email);
    }

    public function getTokenVersion(int $userId): int
    {
        $user = $this->repo->findById($userId);
        if ($user === null) return 1;
        $tokenVersion = $user['token_version'];
        $rawVersion = is_numeric($tokenVersion) ? (int) $tokenVersion : 0;
        return $rawVersion > 0 ? $rawVersion : 1;
    }

    public function verifyEmail(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        $user = $this->repo->findBy('verification_token', $hashedToken);
        if ($user === null) {
            return false;
        }
        $this->repo->updateWhere('id', $user['id'], [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null,
        ]);
        return true;
    }

    public function initiatePasswordReset(string $email): void
    {
        $resetToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $resetToken);
        $this->repo->updateWhere('email', $email, [
            'password_reset_token' => $hashedToken,
            'password_reset_expires_at' => date('Y-m-d H:i:s', time() + 3600),
        ]);
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $hashedToken = hash('sha256', $token);
        $user = $this->repo->findBy('password_reset_token', $hashedToken);
        if ($user === null) {
            return false;
        }
        $expiresAt = $user['password_reset_expires_at'] ?? '';
        $tokenVersion = $user['token_version'] ?? 1;
        /** @var string $expiresAt */
        /** @var int|string $tokenVersion */
        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            return false;
        }
        $passwordHash = self::hashPassword($newPassword);
        $affected = $this->repo->updateWhere('id', $user['id'], [
            'password' => $passwordHash,
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
            'token_version' => (int) $tokenVersion + 1,
        ]);
        if ($affected === 0) return false;
        $this->refreshTokenRepo->revokeAllByUserId($user['id']);
        // M2: Session regeneration required after password reset (API context)
        return true;
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed> */
    public function getAll(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        return $this->repo->findAll($filters, $page, $perPage);
    }

    public function getById(int $id): mixed
    {
        return $this->repo->findById($id);
    }

    /**
     * @param array<string, mixed> $data
     * @return User
     * @throws DuplicateEmailException
     */
    public function create(array $data): User
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
        $verificationToken = hash('sha256', bin2hex(random_bytes(32)));
        /** @var User $user */
        $isFirst = $this->repo->count() === 0;
        $user = $this->repo->create([
            'name' => $data['name'],
            'email' => $email,
            'password' => self::hashPassword($rawPassword),
            'status' => 1,
            'role' => $isFirst ? Role::ADMIN : Role::USER,
            'verification_token' => $verificationToken,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        UserCreatedEvent::dispatch(['id' => $user->id, 'email' => $email, 'name' => $data['name']]);
        return $user;
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
            if ($existing !== null) {
                $existingId = $existing['id'] ?? 0;
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

        return $this->repo->findById($id);
    }

    public function incrementLoginAttempts(int $userId): void
    {
        $table = (new \App\Models\User())->getTable();
        $lockedUntil = date('Y-m-d H:i:s', time() + 900);
        \Siro\Core\Database::execute(
            "UPDATE {$table} SET login_attempts = login_attempts + 1, locked_until = CASE WHEN (login_attempts + 1 >= 5) THEN ? ELSE locked_until END WHERE id = ?",
            [$lockedUntil, $userId]
        );
    }

    public function resetLoginAttempts(int $userId): void
    {
        $this->repo->updateWhere('id', $userId, [
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
