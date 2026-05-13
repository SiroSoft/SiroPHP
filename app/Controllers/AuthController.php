<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\RefreshTokenService;
use App\Services\UserService;
use Siro\Core\Request;
use Siro\Core\Response;
use Throwable;

final class AuthController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    public function register(Request $request): Response
    {
        $request->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|max:255',
        ]);

        $email = strtolower(trim($request->string('email')));

        $existingUser = $this->userService->getByEmail($email);
        if ($existingUser !== null) {
            return Response::error('Validation failed', 422, [
                'email' => ['Email has already been taken'],
            ]);
        }

        try {
            $user = $this->userService->createUser([
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
            'password' => 'required|min:8|max:255',
        ]);

        $email = strtolower(trim($request->string('email')));
        $userData = $this->userService->getByEmail($email);

        if ($userData === null || !isset($userData['password']) || !is_string($userData['password'])) {
            return Response::error('Invalid credentials', 401);
        }

        if ((int) ($userData['status'] ?? 0) !== 1) {
            return Response::error('Account is inactive', 403);
        }

        $lockedUntil = $userData['locked_until'] ?? null;
        if ($lockedUntil !== null && $lockedUntil !== '' && strtotime((string) $lockedUntil) > time()) {
            return Response::error('Account is temporarily locked. Try again later.', 429);
        }

        if (!password_verify($request->string('password'), $userData['password'])) {
            $this->userService->incrementLoginAttempts((int) $userData['id'], (int) ($userData['login_attempts'] ?? 0));
            return Response::error('Invalid credentials', 401);
        }

        $this->userService->resetLoginAttempts((int) $userData['id']);

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

        $tokens = $this->refreshTokenService->verifyAndRotate($request->string('refresh_token'));

        if ($tokens === null) {
            return Response::error('Invalid or expired refresh token', 401);
        }

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

        if (!$this->userService->incrementTokenVersion($userId)) {
            return Response::error('Unable to revoke token', 500);
        }

        return Response::success(null, 'Logout successful. Token revoked.');
    }

    public function verifyEmail(Request $request): Response
    {
        $request->validate(['token' => 'required']);

        $token = $request->string('token');
        $result = $this->userService->verifyEmail($token);

        if (!$result) {
            return Response::error('Invalid verification token', 400);
        }

        return Response::success(null, 'Email verified successfully');
    }

    public function forgotPassword(Request $request): Response
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->string('email')));
        $this->userService->initiatePasswordReset($email);

        return Response::success(null, 'If the email exists, a reset link has been sent.');
    }

    public function resetPassword(Request $request): Response
    {
        $request->validate([
            'token' => 'required',
            'password' => 'required|min:8|max:255',
        ]);

        $token = $request->string('token');
        $result = $this->userService->resetPassword($token, $request->string('password'));

        if (!$result) {
            return Response::error('Invalid or expired reset token', 400);
        }

        return Response::success(null, 'Password reset successfully');
    }

    /** @return array{token:string,refresh_token:string,ttl:int} */
    private function tokenPair(int $userId): array
    {
        return $this->refreshTokenService->createPair($userId);
    }
}
