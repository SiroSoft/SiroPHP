<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\User;
use Siro\Core\Auth\JWT;
use Siro\Core\DB;
use Siro\Core\Env;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Validator;
use Throwable;

final class AuthController
{
    public function register(Request $request): Response
    {
        $data = $request->body();
        $errors = Validator::make($data, [
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        $email = strtolower(trim((string) $request->input('email')));

        $existing = DB::table('users')
            ->select(['id'])
            ->where('email', '=', $email)
            ->first();

        if ($existing !== null) {
            return Response::error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        }

        $passwordHash = password_hash((string) $request->input('password'), PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            return Response::error('Unable to create account', 500);
        }

        try {
            $userId = (int) DB::table('users')->insert([
                'name' => (string) $request->input('name'),
                'email' => $email,
                'password' => $passwordHash,
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable) {
            return Response::error('Unable to create account', 500);
        }

        $tokenData = $this->issueToken($userId);

        return Response::created([
            'token' => $tokenData['token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenData['ttl'],
            'user' => [
                'id' => $userId,
                'name' => (string) $request->input('name'),
                'email' => $email,
            ],
        ], 'Register successful');
    }

    public function login(Request $request): Response
    {
        $data = $request->body();
        $errors = Validator::make($data, [
            'email' => 'required|email|max:255',
            'password' => 'required|min:6|max:255',
        ]);

        if ($errors !== []) {
            return Response::error('Validation failed', 422, $errors);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $user = DB::table('users')
            ->select(['id', 'name', 'email', 'password', 'status'])
            ->where('email', '=', $email)
            ->first();

        if ($user === null || !isset($user['password']) || !is_string($user['password'])) {
            return Response::error('Invalid credentials', 401);
        }

        if ((int) ($user['status'] ?? 0) !== 1) {
            return Response::error('Account is inactive', 403);
        }

        $ok = password_verify((string) $request->input('password'), $user['password']);
        if (!$ok) {
            return Response::error('Invalid credentials', 401);
        }

        $tokenData = $this->issueToken((int) $user['id']);

        return Response::success([
            'token' => $tokenData['token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokenData['ttl'],
            'user' => [
                'id' => (int) $user['id'],
                'name' => (string) ($user['name'] ?? ''),
                'email' => (string) ($user['email'] ?? ''),
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

        $revoked = User::incrementTokenVersion($userId);
        if (!$revoked) {
            return Response::error('Unable to revoke token', 500);
        }

        return Response::success(null, 'Logout successful. Token revoked.');
    }

    /** @return array{token:string,ttl:int} */
    private function issueToken(int $userId): array
    {
        $ttl = max(60, (int) Env::get('JWT_TTL', '3600'));
        $now = time();
        $versionRow = DB::table('users')
            ->select(['token_version'])
            ->where('id', '=', $userId)
            ->first();
        $tokenVersion = (int) ($versionRow['token_version'] ?? 1);

        $token = JWT::encode([
            'sub' => $userId,
            'ver' => $tokenVersion > 0 ? $tokenVersion : 1,
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);

        return ['token' => $token, 'ttl' => $ttl];
    }
}
