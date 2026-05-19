<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use Siro\Core\Auth\JWT;
use Siro\Core\DB;
use Siro\Core\Middleware\MiddlewareInterface;
use Siro\Core\Request;
use Siro\Core\Response;
use Throwable;

/**
 * JWT authentication middleware.
 *
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$roles): mixed
    {
        $header = (string) $request->header('authorization', '');
        if (!str_starts_with(strtolower($header), 'bearer ')) {
            return Response::error('Unauthorized', 401, [
                'token' => ['Invalid or expired token'],
            ]);
        }

        $token = trim(substr($header, 7));

        try {
            $claims = JWT::decode($token);
            /** @var array<string, mixed> $claims */
            $rawSub = $claims['sub'] ?? 0;
            $rawVer = $claims['ver'] ?? 0;
            /** @var int|string $rawSub */
            /** @var int|string $rawVer */
            $userId = (int) $rawSub;
            $tokenVersion = (int) $rawVer;

            if ($userId <= 0 || $tokenVersion <= 0) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['Invalid or expired token'],
                ]);
            }

            // Request-scoped user cache to avoid repeated DB queries for same user
            $userData = self::resolveUser($userId, $tokenVersion);
            if ($userData === null) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['Invalid or expired token'],
                ]);
            }

            $role = $userData['role'];
            /** @var string $role */

            $request->setUser([
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'role' => $role,
                'status' => $userData['status'],
                'token_version' => $userData['token_version'],
                'created_at' => $userData['created_at'],
                'claims' => $claims,
            ]);

            if ($roles !== []) {
                $hasRole = false;
                foreach ($roles as $requiredRole) {
                    if (strtolower($role) === strtolower(trim($requiredRole))) {
                        $hasRole = true;
                        break;
                    }
                }
                if (!$hasRole) {
                    return Response::error('Forbidden', 403, [
                        'role' => ['Insufficient permissions. Required: ' . implode(', ', $roles)],
                    ]);
                }
            }
        } catch (Throwable) {
            return Response::error('Unauthorized', 401, [
                'token' => ['Invalid or expired token'],
            ]);
        }

        return $next($request);
    }

    /** @return array<string, mixed>|null */
    private static function resolveUser(int $userId, int $tokenVersion): ?array
    {
        $rows = DB::table((new User())->getTable())
            ->where('id', '=', $userId)
            ->limit(1)
            ->get();

        $row = $rows[0] ?? null;
        /** @var array<string, mixed>|null $row */
        if ($row === null) {
            return null;
        }

        $rawStatus = $row['status'] ?? 0;
        $rawTokenVersion = $row['token_version'] ?? 1;
        /** @var int|string $rawStatus */
        /** @var int|string $rawTokenVersion */
        $status = (int) $rawStatus;
        $dbTokenVersion = (int) $rawTokenVersion;

        if ($status !== 1 || $dbTokenVersion !== $tokenVersion) {
            return null;
        }

        $rawId = $row['id'] ?? $userId;
        $rawName = $row['name'] ?? '';
        $rawEmail = $row['email'] ?? '';
        $rawRole = $row['role'] ?? 'user';
        $rawCreatedAt = $row['created_at'] ?? '';
        /** @var int|string $rawId */
        /** @var string $rawName */
        /** @var string $rawEmail */
        /** @var string $rawRole */
        /** @var string $rawCreatedAt */

        return [
            'id' => (int) $rawId,
            'name' => $rawName,
            'email' => $rawEmail,
            'role' => $rawRole,
            'status' => $status,
            'token_version' => $dbTokenVersion,
            'created_at' => $rawCreatedAt,
        ];
    }
}
