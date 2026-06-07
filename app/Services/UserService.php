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

final class UserService extends AbstractService
{
    public function __construct(
        UserRepository $repo,
        private readonly RefreshTokenRepository $refreshTokenRepo,
    ) {
        parent::__construct($repo);
    }

    /**
     * Increment the token version for a user, invalidating all existing JWTs.
     * Used on logout to revoke all active sessions.
     *
     * @param int $userId User ID
     * @return bool True if successful, false if user not found
     */
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
        /** @var UserRepository $repo */
        $repo = $this->repo;
        $repo->updateWhere('id', $userId, ['token_version' => $currentVersion + 1]);
        return true;
    }

    /**
     * Find a user by email address. Includes password hash in result.
     *
     * @return array<string, mixed>|null User data array with password, or null if not found
     */
    public function getByEmail(string $email): ?array
    {
        /** @var UserRepository $repo */
        $repo = $this->repo;
        return $repo->findByEmail($email);
    }

    /**
     * Get the current token version for a user.
     * Used when encoding JWT to enforce token invalidation.
     *
     * @param int $userId User ID
     * @return int Token version (minimum 1)
     */
    public function getTokenVersion(int $userId): int
    {
        $user = $this->repo->findById($userId);
        if ($user === null) {
            return 1;
        }
        $tokenVersion = $user['token_version'];
        $rawVersion = is_numeric($tokenVersion) ? (int) $tokenVersion : 0;
        return $rawVersion > 0 ? $rawVersion : 1;
    }

    /**
     * Verify a user's email using a verification token.
     *
     * @param string $token Raw verification token (hashed before lookup)
     * @return bool True if verified, false if token invalid
     */
    public function verifyEmail(string $token): bool
    {
        $hashedToken = hash('sha256', $token);
        /** @var UserRepository $repo */
        $repo = $this->repo;
        $user = $repo->findBy('verification_token', $hashedToken);
        if ($user === null) {
            return false;
        }
        $repo->updateWhere('id', $user['id'], [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null,
        ]);
        return true;
    }

    /**
     * Initiate a password reset for the given email.
     * Stores a hashed reset token with 1-hour expiry.
     * Always succeeds silently to prevent email enumeration.
     *
     * @param string $email User's email address
     */
    public function initiatePasswordReset(string $email): void
    {
        $resetToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $resetToken);
        /** @var UserRepository $repo */
        $repo = $this->repo;
        $repo->updateWhere('email', $email, [
            'password_reset_token' => $hashedToken,
            'password_reset_expires_at' => date('Y-m-d H:i:s', time() + 3600),
        ]);
    }

    /**
     * Reset a user's password using a reset token.
     * Revokes all refresh tokens and increments token_version on success.
     *
     * @param string $token Raw password reset token
     * @param string $newPassword New plaintext password
     * @return bool True if reset successful, false if token invalid/expired
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $hashedToken = hash('sha256', $token);
        /** @var UserRepository $repo */
        $repo = $this->repo;
        $user = $repo->findBy('password_reset_token', $hashedToken);
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
        $affected = $repo->updateWhere('id', $user['id'], [
            'password' => $passwordHash,
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
            'token_version' => (int) $tokenVersion + 1,
        ]);
        if ($affected === 0) {
            return false;
        }
        $this->refreshTokenRepo->revokeAllByUserId(isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : 0);
        return true;
    }

    /**
     * @param array<string, mixed> $data
     * @return \Siro\Core\Model
     * @throws DuplicateEmailException
     */
    public function create(array $data): \Siro\Core\Model
    {
        $rawEmail = $data['email'] ?? '';
        /** @var string $rawEmail */
        $email = strtolower(trim($rawEmail));

        /** @var UserRepository $repo */
        $repo = $this->repo;
        $existing = $repo->findByEmail($email);
        if ($existing !== null) {
            throw new DuplicateEmailException($email);
        }

        $rawPassword = $data['password'] ?? '';
        /** @var string $rawPassword */
        $verificationToken = hash('sha256', bin2hex(random_bytes(32)));
        $isFirst = $this->repo->count() === 0;
        $user = $repo->create([
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
     * @return \Siro\Core\Model|null
     * @throws DuplicateEmailException
     * @throws NoFieldsToUpdateException
     */
    public function update(int $id, array $data): ?\Siro\Core\Model
    {
        $user = $this->repo->findById($id);
        if ($user === null) {
            return null;
        }

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $rawEmail = $data['email'];
            /** @var string $rawEmail */
            $email = strtolower(trim($rawEmail));
            /** @var UserRepository $repo */
            $repo = $this->repo;
            $existing = $repo->findByEmail($email);
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

    /**
     * Record a failed login attempt and lock the account if >= 5 attempts.
     *
     * @param int $userId User ID
     */
    public function recordLoginAttempt(int $userId): void
    {
        $table = (new User())->getTable();
        $lockedUntil = date('Y-m-d H:i:s', time() + 900);
        \Siro\Core\Database::execute(
            "UPDATE {$table} SET login_attempts = login_attempts + 1, locked_until = CASE WHEN (login_attempts + 1 >= 5) THEN ? ELSE locked_until END WHERE id = ?",
            [$lockedUntil, $userId]
        );
    }

    /**
     * Reset login attempts counter and unlock the account.
     *
     * @param int $userId User ID
     */
    public function resetLoginAttempts(int $userId): void
    {
        /** @var UserRepository $repo */
        $repo = $this->repo;
        $repo->updateWhere('id', $userId, [
            'login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Check if a user account is currently locked due to too many failed attempts.
     *
     * @param int $userId User ID
     * @return bool True if locked_until is set and still in the future
     */
    public function isLocked(int $userId): bool
    {
        $user = $this->repo->findById($userId);
        if ($user === null) {
            return false;
        }
        $lockedUntil = $user['locked_until'] ?? null;
        if ($lockedUntil === null || $lockedUntil === '' || !is_string($lockedUntil)) {
            return false;
        }
        return strtotime($lockedUntil) > time();
    }

    /**
     * Change the authenticated user's password.
     *
     * @param int $userId User ID
     * @param string $currentPassword The current password for verification
     * @param string $newPassword The new password
     * @return array{success: bool, error?: string, code?: int}
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = $this->repo->findById($userId);
        if ($user === null) {
            return ['success' => false, 'error' => 'User not found', 'code' => 404];
        }
        $existingPassword = $user->getAttribute('password');
        if (!is_string($existingPassword) || !password_verify($currentPassword, $existingPassword)) {
            return ['success' => false, 'error' => 'Current password is incorrect', 'code' => 400];
        }
        $this->repo->update($userId, ['password' => password_hash($newPassword, PASSWORD_BCRYPT)]);
        return ['success' => true, 'code' => 200];
    }

    private static function hashPassword(string $password): string
    {
        return password_hash($password, \PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
