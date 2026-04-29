<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\User as UserService;
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
        $user = User::where('verification_token', '=', $token)->first();

        if ($user === null) {
            return Response::error('Invalid verification token', 400);
        }

        $user->update([
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null,
        ]);

        return Response::success(null, 'Email verified successfully');
    }

    public function forgotPassword(Request $request): Response
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->string('email')));
        $user = User::where('email', '=', $email)->first();

        if ($user !== null) {
            $resetToken = bin2hex(random_bytes(32));
            $user->update([
                'password_reset_token' => $resetToken,
                'password_reset_expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);
        }

        // Always return success to prevent email enumeration
        return Response::success(null, 'If the email exists, a reset link has been sent.');
    }

    public function resetPassword(Request $request): Response
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:6|max:255',
        ]);

        $token = $request->string('token');
        $user = User::where('password_reset_token', '=', $token)->first();

        if ($user === null) {
            return Response::error('Invalid or expired reset token', 400);
        }

        $userData = $user->toArray();
        $expiresAt = (string) ($userData['password_reset_expires_at'] ?? '');

        if ($expiresAt !== '' && strtotime($expiresAt) < time()) {
            return Response::error('Reset token has expired', 400);
        }

        $passwordHash = password_hash($request->string('password'), PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            return Response::error('Unable to reset password', 500);
        }

        $user->update([
            'password' => $passwordHash,
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
            'token_version' => ($userData['token_version'] ?? 1) + 1,
        ]);

        return Response::success(null, 'Password reset successfully');
    }

    /** @return array{token:string,refresh_token:string,ttl:int} */
    private function tokenPair(int $userId): array
    {
        $ttl = max(60, (int) Env::get('JWT_TTL', '3600'));
        $refreshTtl = max(3600, (int) Env::get('JWT_REFRESH_TTL', '604800'));

        $user = User::find($userId);
        $tokenVersion = (int) ($user?->token_version ?? 1);

        $token = JWT::encodeAccess($userId, $tokenVersion, $ttl);
        $refreshToken = JWT::encodeRefresh($userId, $tokenVersion, $refreshTtl);

        // Store refresh token
        $jti = bin2hex(random_bytes(16));
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