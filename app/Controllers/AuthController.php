<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\User as UserService;
use Siro\Core\Auth\JWT;
use Siro\Core\Env;
use Siro\Core\Request;
use Siro\Core\Response;
use Throwable;

final class AuthController
{
    public function register(Request $request): Response
    {
        $request->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        $email = strtolower(trim($request->string('email')));

        $existing = User::where('email', '=', $email)->first();
        if ($existing !== null) {
            return Response::error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        }

        $passwordHash = password_hash($request->string('password'), PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            return Response::error('Unable to create account', 500);
        }

        try {
            $user = User::create([
                'name' => $request->string('name'),
                'email' => $email,
                'password' => $passwordHash,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            return Response::error('Unable to create account', 500);
        }

        $tokenData = $this->issueToken((int) $user->id);

        return Response::created([
            'token' => $tokenData['token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenData['ttl'],
            'user' => [
                'id' => (int) $user->id,
                'name' => $request->string('name'),
                'email' => $email,
            ],
        ], 'Register successful');
    }

    public function login(Request $request): Response
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        $email = strtolower(trim($request->string('email')));
        $rows = User::where('email', '=', $email)->limit(1)->get();
        $userData = $rows[0] ?? null;

        if ($userData === null || !isset($userData['password']) || !is_string($userData['password'])) {
            return Response::error('Invalid credentials', 401);
        }

        if ((int) ($userData['status'] ?? 0) !== 1) {
            return Response::error('Account is inactive', 403);
        }

        if (!password_verify($request->string('password'), $userData['password'])) {
            return Response::error('Invalid credentials', 401);
        }

        $tokenData = $this->issueToken((int) $userData['id']);

        return Response::success([
            'token' => $tokenData['token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenData['ttl'],
            'user' => [
                'id' => (int) $userData['id'],
                'name' => (string) ($userData['name'] ?? ''),
                'email' => (string) ($userData['email'] ?? ''),
            ],
        ], 'Login successful');
    }

    public function me(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            return Response::error('Unauthorized', 401);
        }

        unset($user['claims']);
        return Response::success($user, 'Authenticated user');
    }

    public function logout(Request $request): Response
    {
        $user = $request->user();
        $userId = (int) ($user['id'] ?? 0);

        if ($userId <= 0) {
            return Response::error('Unauthorized', 401);
        }

        if (!UserService::incrementTokenVersion($userId)) {
            return Response::error('Unable to revoke token', 500);
        }

        return Response::success(null, 'Logout successful. Token revoked.');
    }

    /** @return array{token:string,ttl:int} */
    private function issueToken(int $userId): array
    {
        $ttl = max(60, (int) Env::get('JWT_TTL', '3600'));
        $now = time();

        $user = User::find($userId);
        $tokenVersion = (int) ($user?->token_version ?? 1);

        $token = JWT::encode([
            'sub' => $userId,
            'ver' => $tokenVersion > 0 ? $tokenVersion : 1,
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);

        return ['token' => $token, 'ttl' => $ttl];
    }
}
