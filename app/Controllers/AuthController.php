<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;
use Siro\Core\Auth\JWT;
use Siro\Core\DB;
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

        $existingUser = UserService::getByEmail($email);
        if ($existingUser !== null) {
            return Response::error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        }

        try {
            $user = UserService::createUser([
                'name' => $request->string('name'),
                'email' => $email,
                'password' => $request->string('password'),
            ]);
        } catch (Throwable) {
            return Response::error('Unable to create account', 500);
        }

        $userId = (int) $user->id;
        $tokens = $this->tokenPair($userId);

        return Response::created([
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['ttl'],
            'user' => [
                'id' => $userId,
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
        $userData = UserService::getByEmail($email);

        if ($userData === null || !isset($userData['password']) || !is_string($userData['password'])) {
            return Response::error('Invalid credentials', 401);
        }

        if ((int) ($userData['status'] ?? 0) !== 1) {
            return Response::error('Account is inactive', 403);
        }

        if (!password_verify($request->string('password'), $userData['password'])) {
            return Response::error('Invalid credentials', 401);
        }

        $userId = (int) $userData['id'];
        $tokens = $this->tokenPair($userId);

        return Response::success([
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['ttl'],
            'user' => [
                'id' => $userId,
                'name' => (string) ($userData['name'] ?? ''),
                'email' => (string) ($userData['email'] ?? ''),
            ],
        ], 'Login successful');
    }

    public function refresh(Request $request): Response
    {
        $request->validate(['refresh_token' => 'required']);

        $refreshToken = $request->string('refresh_token');

        try {
            $claims = JWT::decode($refreshToken);
        } catch (Throwable) {
            return Response::error('Invalid or expired refresh token', 401);
        }

        if (($claims['type'] ?? '') !== JWT::TYPE_REFRESH) {
            return Response::error('Invalid token type', 401);
        }

        $userId = (int) ($claims['sub'] ?? 0);
        $jti = (string) ($claims['jti'] ?? '');

        if ($userId <= 0 || $jti === '') {
            return Response::error('Invalid token', 401);
        }

        // Check refresh token was not revoked
        $stored = DB::table('refresh_tokens')
            ->where('jti', '=', $jti)
            ->where('revoked', '=', 0)
            ->first();

        if ($stored === null) {
            return Response::error('Refresh token revoked', 401);
        }

        // Revoke old refresh token (rotation)
        DB::table('refresh_tokens')
            ->where('jti', '=', $jti)
            ->update(['revoked' => 1]);

        $tokens = $this->tokenPair($userId);

        return Response::success([
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['ttl'],
        ], 'Token refreshed');
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

    public function verifyEmail(Request $request): Response
    {
        $request->validate(['token' => 'required']);

        $token = $request->string('token');
        $result = UserService::verifyEmail($token);

        if (!$result) {
            return Response::error('Invalid verification token', 400);
        }

        return Response::success(null, 'Email verified successfully');
    }

    public function forgotPassword(Request $request): Response
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->string('email')));
        UserService::initiatePasswordReset($email);

        return Response::success(null, 'If the email exists, a reset link has been sent.');
    }

    public function resetPassword(Request $request): Response
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|max:255',
        ]);

        $token = $request->string('token');
        $result = UserService::resetPassword($token, $request->string('password'));

        if (!$result) {
            return Response::error('Invalid or expired reset token', 400);
        }

        return Response::success(null, 'Password reset successfully');
    }

    /** @return array{token:string,refresh_token:string,ttl:int} */
    private function tokenPair(int $userId): array
    {
        $ttl = max(60, (int) Env::get('JWT_TTL', '3600'));
        $refreshTtl = max(3600, (int) Env::get('JWT_REFRESH_TTL', '604800'));

        $tokenVersion = UserService::getTokenVersion($userId);

        $token = JWT::encodeAccess($userId, $tokenVersion, $ttl);
        $jti = bin2hex(random_bytes(16));
        $refreshToken = JWT::encodeRefresh($userId, $tokenVersion, $refreshTtl, $jti);
        DB::table('refresh_tokens')->insert([
            'jti' => $jti,
            'user_id' => $userId,
            'revoked' => 0,
            'expires_at' => date('Y-m-d H:i:s', time() + $refreshTtl),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'ttl' => $ttl,
        ];
    }
}
