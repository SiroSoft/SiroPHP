<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RefreshTokenRepository;
use Siro\Core\Auth\JWT;
use Siro\Core\Env;

class RefreshTokenService
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
        if ($stored === null) return null;

        $this->refreshTokenRepo->revokeByJti($jti);

        return $this->createPair($userId);
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->refreshTokenRepo->revokeAllByUserId($userId);
    }
}
