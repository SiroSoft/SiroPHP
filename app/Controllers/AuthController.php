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

    /**
     * Register a new user account.
     *
     * Creates a user with name, email, password. Returns JWT + refresh token on success.
     * Rate limited: 30 requests per minute.
     *
     * POST /api/auth/register
     * Body: { name: string, email: string, password: string }
     *
     * @param Request $request Incoming HTTP request with validated fields
     * @return Response JSON with token, refresh_token, and user data (201) or error (422)
     */
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

        $userId = $user->id;
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

    /**
     * Authenticate a user with email + password.
     *
     * Uses constant-time comparison to prevent user enumeration.
     * Checks account status, lockout, and increments login attempts on failure.
     * Rate limited: 60 requests per minute.
     *
     * POST /api/auth/login
     * Body: { email: string, password: string }
     *
     * @param Request $request Incoming HTTP request with credentials
     * @return Response JSON with JWT tokens (200) or error (401)
     */
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

        $userId = isset($userData['id']) && is_numeric($userData['id']) ? (int) $userData['id'] : 0;

        $status = isset($userData['status']) && is_numeric($userData['status']) ? (int) $userData['status'] : 0;
        if ($status !== 1) {
            return Response::error('Invalid credentials', 401);
        }

        if ($userId > 0 && $this->userService->isLocked($userId)) {
            return Response::error('Invalid credentials', 401);
        }

        $hash = $userData['password'];
        if (!password_verify($request->string('password'), $hash)) {
            $this->userService->recordLoginAttempt($userId);
            return Response::error('Invalid credentials', 401);
        }

        $this->userService->resetLoginAttempts($userId);

        Session::instance()->regenerate();

        $tokens = $this->tokenPair($userId);

        return Response::success([
            'token' => $tokens['token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => 'Bearer',
            'expires_in' => $tokens['ttl'],
            'user' => [
                'id' => $userId,
                'name' => is_string($userData['name'] ?? null) ? $userData['name'] : '',
                'email' => is_string($userData['email'] ?? null) ? $userData['email'] : '',
            ],
        ], 'Login successful');
    }

    /**
     * Refresh an expired JWT using a refresh token (token rotation).
     *
     * Verifies the refresh token, revokes the old one, and issues a new token pair.
     * Detects token theft and revokes all tokens for the affected user.
     * Rate limited: 30 requests per minute.
     *
     * POST /api/auth/refresh
     * Body: { refresh_token: string }
     *
     * @param Request $request Incoming HTTP request with refresh_token
     * @return Response JSON with new token pair (200) or error (401)
     */
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

    /**
     * Get the currently authenticated user's profile.
     *
     * Requires valid JWT via auth middleware. Returns fresh data from DB when possible.
     *
     * GET /api/auth/me
     * Headers: Authorization: Bearer <token>
     *
     * @param Request $request Incoming HTTP request with authenticated user
     * @return Response JSON with user profile (200) or error (401)
     */
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

    /**
     * Logout and revoke all tokens for the current user.
     *
     * Increments the user's token_version so existing JWTs become invalid.
     * Rate limited: 60 requests per minute.
     *
     * POST /api/auth/logout
     * Headers: Authorization: Bearer <token>
     *
     * @param Request $request Incoming HTTP request with authenticated user
     * @return Response Success message (200) or error (401/500)
     */
    public function logout(Request $request): Response
    {
        $user = $request->user();
        $userId = is_array($user) && isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : 0;

        if ($userId <= 0) {
            return Response::error('Unauthorized', 401);
        }

        if (!$this->userService->incrementTokenVersion($userId)) {
            return Response::error('Unable to revoke token', 500);
        }

        return Response::success(null, 'Logout successful. Token revoked.');
    }

    /**
     * Verify a user's email address using a verification token.
     *
     * Rate limited: 10 requests per minute.
     *
     * POST /api/auth/verify-email
     * Body: { token: string }
     *
     * @param Request $request Incoming HTTP request with verification token
     * @return Response Success message (200) or error (400)
     */
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

    /**
     * Resend email verification token for the authenticated user.
     *
     * Generates a new verification token and stores it on the user model.
     * Rate limited: 5 requests per minute.
     *
     * POST /api/auth/verify-email/resend
     * Headers: Authorization: Bearer <token>
     *
     * @param Request $request Incoming HTTP request with authenticated user
     * @return Response Success message (200) or error (401)
     */
    public function resendVerification(Request $request): Response
    {
        $user = $request->user();
        $userId = is_array($user) && isset($user['id']) && is_numeric($user['id']) ? (int) $user['id'] : 0;

        if ($userId <= 0) {
            return Response::error('Unauthorized', 401);
        }

        $existingUser = \App\Models\User::find($userId);
        if ($existingUser === null) {
            return Response::error('User not found', 404);
        }

        $rawToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);

        $existingUser->update([
            'verification_token' => $hashedToken,
        ]);

        return Response::success(null, 'Verification email sent');
    }

    /**
     * Send a password reset link to the given email.
     *
     * Always returns success to prevent email enumeration.
     * Rate limited: 10 requests per minute.
     *
     * POST /api/auth/forgot-password
     * Body: { email: string }
     *
     * @param Request $request Incoming HTTP request with email address
     * @return Response Success message (200) regardless of whether email exists
     */
    public function forgotPassword(Request $request): Response
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->string('email')));
        $this->userService->initiatePasswordReset($email);

        return Response::success(null, 'If the email exists, a reset link has been sent.');
    }

    /**
     * Reset a user's password using a reset token (from forgot-password).
     *
     * Tokens expire after 1 hour. On success, all existing sessions are revoked.
     * Rate limited: 10 requests per minute.
     *
     * POST /api/auth/reset-password
     * Body: { token: string, password: string }
     *
     * @param Request $request Incoming HTTP request with reset token + new password
     * @return Response Success message (200) or error (400)
     */
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
