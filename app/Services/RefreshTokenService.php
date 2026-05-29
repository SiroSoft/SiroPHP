<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RefreshTokenRepository;
use Siro\Core\Auth\JWT;
use Siro\Core\Env;
use Siro\Core\Logger;

final class RefreshTokenService
{
    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepo,
        private readonly UserService $userService,
    ) {
    }

    /** @return array{token: string, refresh_token: string, ttl: int} */
    public function createPair(int $userId): array
    {
        $ttl = max(60, (int) Env::get('JWT_TTL', '3600'));
        $refreshTtl = max(3600, (int) Env::get('JWT_REFRESH_TTL', '604800'));

        $tokenVersion = $this->userService->getTokenVersion($userId);
        $token = JWT::encodeAccess($userId, $tokenVersion, $ttl);
        $jti = bin2hex(random_bytes(16));
        $refreshToken = JWT::encodeRefresh($userId, $tokenVersion, $refreshTtl, $jti);

        $this->refreshTokenRepo->create($jti, $userId, $refreshTtl);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'ttl' => $ttl,
        ];
    }

    /** @return array{token: string, refresh_token: string, ttl: int}|null */
    public function verifyAndRotate(string $refreshToken): ?array
    {
        try {
            $claims = JWT::decode($refreshToken);
        } catch (\Throwable) {
            return null;
        }

        /** @var array<string, mixed> $claims */
        if (($claims['type'] ?? '') !== JWT::TYPE_REFRESH) return null;

        $rawUserId = $claims['sub'] ?? 0;
        $rawJti = $claims['jti'] ?? '';
        /** @var int|string $rawUserId */
        /** @var string $rawJti */
        $userId = (int) $rawUserId;
        $jti = $rawJti;

        if ($userId <= 0 || $jti === '') return null;

        $stored = $this->refreshTokenRepo->findActiveByJti($jti);
        if ($stored === null) {
            // Check for token theft: revoked token being reused
            $revoked = $this->refreshTokenRepo->findRevokedByJti($jti);
            if ($revoked !== null) {
                $rawUserId = $revoked['user_id'] ?? 0;
                /** @var int|string $rawUserId */
                $theftUserId = (int) $rawUserId;
                Logger::security('token.theft', [
                    'jti' => $jti,
                    'user_id' => $theftUserId,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ]);
                // Revoke all tokens for the affected user as a precaution
                if ($theftUserId > 0) {
                    $this->revokeAllForUser($theftUserId);
                    $this->userService->incrementTokenVersion($theftUserId);
                }
            }
            return null;
        }

        $this->refreshTokenRepo->revokeByJti($jti);

        return $this->createPair($userId);
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->refreshTokenRepo->revokeAllByUserId($userId);
    }
}
