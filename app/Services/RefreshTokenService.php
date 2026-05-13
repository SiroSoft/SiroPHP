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

    public function verifyAndRotate(string $refreshToken): ?array
    {
        try {
            $claims = JWT::decode($refreshToken);
        } catch (\Throwable) {
            return null;
        }

        if (($claims['type'] ?? '') !== JWT::TYPE_REFRESH) return null;

        $userId = (int) ($claims['sub'] ?? 0);
        $jti = (string) ($claims['jti'] ?? '');

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
