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
    /** @var array<int, array<string, mixed>|null> */
    private static array $userCache = [];

    public function handle(Request $request, callable $next, string ...$roles): mixed
    {
        $header = (string) $request->header('authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return Response::error('Unauthorized', 401, [
                'token' => ['Missing bearer token'],
            ]);
        }

        $token = trim($matches[1]);
        if ($token === '') {
            return Response::error('Unauthorized', 401, [
                'token' => ['Missing bearer token'],
            ]);
        }

        try {
            $claims = JWT::decode($token);
            $userId = (int) ($claims['sub'] ?? 0);
            $tokenVersion = (int) ($claims['ver'] ?? 0);

            if ($userId <= 0) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['Invalid token subject'],
                ]);
            }

            if ($tokenVersion <= 0) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['Invalid token version'],
                ]);
            }

            // Request-scoped user cache to avoid repeated DB queries for same user
            $userData = self::resolveUser($userId, $tokenVersion);
            if ($userData === null) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['User not found, inactive, or token revoked'],
                ]);
            }

            $role = $userData['role'];

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
        if (isset(self::$userCache[$userId])) {
            return self::$userCache[$userId];
        }

        $rows = DB::table((new User())->getTable())
            ->where('id', '=', $userId)
            ->limit(1)
            ->get();

        $row = $rows[0] ?? null;
        if ($row === null) {
            self::$userCache[$userId] = null;
            return null;
        }

        $status = (int) ($row['status'] ?? 0);
        $dbTokenVersion = (int) ($row['token_version'] ?? 1);

        if ($status !== 1 || $dbTokenVersion !== $tokenVersion) {
            self::$userCache[$userId] = null;
            return null;
        }

        $userData = [
            'id' => (int) ($row['id'] ?? $userId),
            'name' => (string) ($row['name'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'role' => (string) ($row['role'] ?? 'user'),
            'status' => $status,
            'token_version' => $dbTokenVersion,
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];

        self::$userCache[$userId] = $userData;
        return $userData;
    }
}
