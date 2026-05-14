<?php

declare(strict_types=1);

namespace App\Controllers;

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

        /** @var array<string, mixed> $userData */
        $status = $userData['status'] ?? 0;
        /** @var int|string $status */
        if ((int) $status !== 1) {
            return Response::error('Account is inactive', 403);
        }

        $lockedUntil = $userData['locked_until'] ?? null;
        /** @var string|null $lockedUntil */
        if ($lockedUntil !== null && $lockedUntil !== '' && strtotime($lockedUntil) > time()) {
            return Response::error('Account is temporarily locked. Try again later.', 429);
        }

        $hash = $userData['password'];
        /** @var string $hash */
        if (!password_verify($request->string('password'), $hash)) {
            $userId = $userData['id'];
            $loginAttempts = $userData['login_attempts'] ?? 0;
            /** @var int|string $userId */
            /** @var int|string $loginAttempts */
            $this->userService->incrementLoginAttempts((int) $userId, (int) $loginAttempts);
            return Response::error('Invalid credentials', 401);
        }

        $userId = $userData['id'];
        /** @var int|string $userId */
        $this->userService->resetLoginAttempts((int) $userId);

        Session::instance()->regenerate();

        $tokens = $this->tokenPair((int) $userId);

        $name = $userData['name'] ?? '';
        $emailField = $userData['email'] ?? '';
        /** @var string $name */
        /** @var string $emailField */

        return Response::success([
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['ttl'],
            'user' => [
                'id' => (int) $userId,
                'name' => $name,
                'email' => $emailField,
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
        /** @var array<string, mixed>|null $user */
        $rawId = $user['id'] ?? 0;
        /** @var int|string $rawId */
        $userId = (int) $rawId;

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
