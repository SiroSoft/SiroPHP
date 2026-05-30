<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\DuplicateEmailException;
use App\Services\RefreshTokenService;
use App\Services\UserService;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Session;
use Throwable;

final class AuthController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    // Rate limited: 30 requests per minute
    public function register(Request $request): Response
    {
        $request->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|max:255',
        ]);

        $email = strtolower(trim($request->string('email')));

        try {
            $user = $this->userService->create([
                'name' => $request->string('name'),
                'email' => $email,
                'password' => $request->string('password'),
            ]);
        } catch (DuplicateEmailException) {
            return Response::error('Validation failed', 422, [
                'email' => ['The email has already been taken'],
            ]);
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

    // Rate limited: 60 requests per minute. Constant-time credential check.
    public function login(Request $request): Response
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|max:255',
        ]);

        $email = strtolower(trim($request->string('email')));
        $userData = $this->userService->getByEmail($email);

        if ($userData === null || !isset($userData['password']) || !is_string($userData['password'])) {
            // Normalize timing to prevent user enumeration
            password_verify('dummy', '$2y$12$dummyhashdummyhashdummyhashdummyhashdummyhashdummyhashdummyhashdu');
            return Response::error('Invalid credentials', 401);
        }

        $status = $userData['status'] ?? 0;
        if ((int) $status !== 1) {
            return Response::error('Invalid credentials', 401);
        }

        $lockedUntil = $userData['locked_until'] ?? null;
        if ($lockedUntil !== null && $lockedUntil !== '' && strtotime($lockedUntil) > time()) {
            return Response::error('Invalid credentials', 401);
        }

        $hash = $userData['password'];
        if (!password_verify($request->string('password'), $hash)) {
            $userId = $userData['id'];
            $this->userService->incrementLoginAttempts((int) $userId);
            return Response::error('Invalid credentials', 401);
        }

        $userId = $userData['id'];
        $this->userService->resetLoginAttempts((int) $userId);

        Session::instance()->regenerate();

        $tokens = $this->tokenPair((int) $userId);

        return Response::success([
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['ttl'],
            'user' => [
                'id' => (int) $userId,
                'name' => $userData['name'] ?? '',
                'email' => $userData['email'] ?? '',
            ],
        ], 'Login successful');
    }

    // Rate limited: 30 requests per minute
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

    // Protected: auth middleware
    public function me(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            return Response::error('Unauthorized', 401);
        }

        $userId = isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : 0;
        if ($userId > 0) {
            $freshUser = $this->userService->getById($userId);
            if ($freshUser !== null) {
                return Response::success(\App\Resources\UserResource::make($freshUser), 'Authenticated user');
            }
        }

        unset($user['claims']);
        return Response::success($user, 'Authenticated user');
    }

    // Protected: auth middleware
    public function logout(Request $request): Response
    {
        $user = $request->user();
        $rawId = $user['id'] ?? 0;
        $userId = (int) $rawId;

        if ($userId <= 0) {
            return Response::error('Unauthorized', 401);
        }

        if (!$this->userService->incrementTokenVersion($userId)) {
            return Response::error('Unable to revoke token', 500);
        }

        return Response::success(null, 'Logout successful. Token revoked.');
    }

    // Rate limited: 10 requests per minute
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

    // Rate limited: 10 requests per minute
    public function forgotPassword(Request $request): Response
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->string('email')));
        $this->userService->initiatePasswordReset($email);

        return Response::success(null, 'If the email exists, a reset link has been sent.');
    }

    // Rate limited: 10 requests per minute
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
