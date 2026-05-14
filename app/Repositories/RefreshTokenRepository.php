<?php

declare(strict_types=1);

namespace App\Repositories;

use Siro\Core\DB;

class RefreshTokenRepository
{
    /** @return array<string, mixed>|null */
    public function findActiveByJti(string $jti): ?array
    {
        $row = DB::table('refresh_tokens')
            ->where('jti', '=', $jti)
            ->where('revoked', '=', 0)
            ->first();
        return $row;
    }

    public function revokeByJti(string $jti): void
    {
        DB::table('refresh_tokens')
            ->where('jti', '=', $jti)
            ->update(['revoked' => 1]);
    }

    public function create(string $jti, int $userId, int $ttl): void
    {
        DB::table('refresh_tokens')->insert([
            'jti' => $jti,
            'user_id' => $userId,
            'revoked' => 0,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function revokeAllByUserId(int $userId): void
    {
        DB::table('refresh_tokens')
            ->where('user_id', '=', $userId)
            ->where('revoked', '=', 0)
            ->update(['revoked' => 1]);
    }
}
