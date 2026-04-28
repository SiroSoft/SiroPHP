<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\User;
use Siro\Core\Auth\JWT;
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

            $user = User::find($userId);

            if ($user === null || ((int) $user->status !== 1)) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['User not found or inactive'],
                ]);
            }

            $userData = $user->toArray();
            if ((int) ($userData['token_version'] ?? 1) !== $tokenVersion) {
                return Response::error('Unauthorized', 401, [
                    'token' => ['Token has been revoked'],
                ]);
            }

            $request->setUser([
                'id' => (int) $userData['id'],
                'name' => (string) ($userData['name'] ?? ''),
                'email' => (string) ($userData['email'] ?? ''),
                'status' => (int) ($userData['status'] ?? 0),
                'token_version' => (int) ($userData['token_version'] ?? 1),
                'created_at' => (string) ($userData['created_at'] ?? ''),
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
