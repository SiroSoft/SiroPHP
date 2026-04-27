<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Auth\JWT;
use Siro\Core\DB;
use Siro\Core\Request;
use Siro\Core\Response;
use Throwable;

final class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $header = (string) $request->header('authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return Response::error('Unauthorized', 401, [
                'token' => ['Missing bearer token'],
            ]);
        }

        $token = trim((string) ($matches[1] ?? ''));
        if ($token === '') {
            return Response::error('Unauthorized', 401, [
                'token' => ['Missing bearer token'],
            ]);
        }

        try {
            $claims = JWT::decode($token);
            $userId = (int) ($claims['sub'] ?? 0);
            if ($userId <= 0) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['Invalid token subject'],
                ]);
            }

            $user = DB::table('users')
                ->select(['id', 'name', 'email', 'status', 'created_at'])
                ->where('id', '=', $userId)
                ->first();

            if ($user === null || (isset($user['status']) && (int) $user['status'] !== 1)) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['User not found or inactive'],
                ]);
            }

            $request->setUser([
                'id' => (int) $user['id'],
                'name' => (string) ($user['name'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
                'status' => (int) ($user['status'] ?? 0),
                'created_at' => (string) ($user['created_at'] ?? ''),
                'claims' => $claims,
            ]);
        } catch (Throwable) {
            return Response::error('Unauthorized', 401, [
                'token' => ['Invalid or expired token'],
            ]);
        }

        return $next($request);
    }
}
