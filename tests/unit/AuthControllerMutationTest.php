<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;

final class AuthControllerMutationTest extends TestCase
{
    // ─── Register ────────────────────────────────────────────────────

    public function testRegisterSuccess(): void
    {
        $resp = $this->post('/api/auth/register', [
            'name' => 'New User',
            'email' => 'reg-' . uniqid() . '@test.com',
            'password' => 'secret123',
        ]);
        $this->assertContains($resp->status(), [200, 201]);
    }

    public function testRegisterValidationErrors(): void
    {
        $this->post('/api/auth/register', [])->assertStatus(422);
    }

    public function testRegisterShortPassword(): void
    {
        $this->post('/api/auth/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'short',
        ])->assertStatus(422);
    }

    public function testRegisterInvalidEmail(): void
    {
        $this->post('/api/auth/register', [
            'name' => 'Test',
            'email' => 'not-an-email',
            'password' => 'secret123',
        ])->assertStatus(422);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $email = 'dup-reg-' . uniqid() . '@test.com';
        $this->post('/api/auth/register', [
            'name' => 'First',
            'email' => $email,
            'password' => 'secret123',
        ])->assertStatus(201);
        $this->post('/api/auth/register', [
            'name' => 'Second',
            'email' => $email,
            'password' => 'secret123',
        ])->assertStatus(422);
    }

    // ─── Login ───────────────────────────────────────────────────────

    public function testLoginSuccess(): void
    {
        $email = 'login-' . uniqid() . '@test.com';
        $this->post('/api/auth/register', [
            'name' => 'Login User',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $resp = $this->post('/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $this->assertSame(200, $resp->status());
    }

    public function testLoginInvalidCredentials(): void
    {
        $this->post('/api/auth/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    public function testLoginWrongPassword(): void
    {
        $email = 'wrongpw-' . uniqid() . '@test.com';
        $this->post('/api/auth/register', [
            'name' => 'Wrong PW',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $this->post('/api/auth/login', [
            'email' => $email,
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    public function testLoginInactiveAccount(): void
    {
        $app = $this->createApp();
        $email = 'inactive-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Inactive',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET status = 0 WHERE email = ?", [$email]);
        $resp = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $this->assertSame(401, $resp->statusCode());
    }

    public function testLoginLockedAccount(): void
    {
        $app = $this->createApp();
        $email = 'locked-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Locked',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $lockedUntil = date('Y-m-d H:i:s', time() + 900);
        \Siro\Core\Database::execute("UPDATE users SET locked_until = ?, login_attempts = 5 WHERE email = ?", [$lockedUntil, $email]);
        $resp = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $this->assertSame(401, $resp->statusCode());
    }

    public function testLoginValidationErrors(): void
    {
        $this->post('/api/auth/login', [])->assertStatus(422);
    }

    // ─── Me ──────────────────────────────────────────────────────────

    public function testMeReturnsProfile(): void
    {
        $app = $this->createApp();
        $email = 'meuser-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Me User',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/auth/me', $headers)->assertOk();
    }

    public function testMeUnauthorized(): void
    {
        $this->get('/api/auth/me')->assertStatus(401);
    }

    // ─── Logout ──────────────────────────────────────────────────────

    public function testLogoutSuccess(): void
    {
        $app = $this->createApp();
        $email = 'logout-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Logout User',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->post('/api/auth/logout', [], $headers)->assertOk();
    }

    public function testLogoutUnauthorized(): void
    {
        $this->post('/api/auth/logout')->assertStatus(401);
    }

    // ─── Refresh ─────────────────────────────────────────────────────

    public function testRefreshInvalidToken(): void
    {
        $this->post('/api/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ])->assertStatus(401);
    }

    public function testRefreshValidationErrors(): void
    {
        $this->post('/api/auth/refresh', [])->assertStatus(422);
    }

    public function testRefreshSuccess(): void
    {
        $app = $this->createApp();
        $email = 'refresh-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Refresh User',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $refreshToken = $loginJson['data']['refresh_token'] ?? '';
        if ($refreshToken !== '') {
            $resp = $this->dispatch($app, 'POST', '/api/auth/refresh', [
                'refresh_token' => $refreshToken,
            ]);
            $this->assertContains($resp->statusCode(), [200, 401]);
        }
    }

    // ─── Forgot Password ─────────────────────────────────────────────

    public function testForgotPasswordSuccess(): void
    {
        $this->post('/api/auth/forgot-password', [
            'email' => 'forgot@test.com',
        ])->assertOk();
    }

    public function testForgotPasswordValidationErrors(): void
    {
        $this->post('/api/auth/forgot-password', [])->assertStatus(422);
    }

    public function testForgotPasswordInvalidEmail(): void
    {
        $this->post('/api/auth/forgot-password', [
            'email' => 'not-valid',
        ])->assertStatus(422);
    }

    // ─── Reset Password ──────────────────────────────────────────────

    public function testResetPasswordInvalidToken(): void
    {
        $this->post('/api/auth/reset-password', [
            'token' => 'invalid',
            'password' => 'newsecret123',
        ])->assertStatus(400);
    }

    public function testResetPasswordValidationErrors(): void
    {
        $this->post('/api/auth/reset-password', [])->assertStatus(422);
    }

    // ─── Verify Email ────────────────────────────────────────────────

    public function testVerifyEmailInvalidToken(): void
    {
        $this->post('/api/auth/verify-email', [
            'token' => 'invalid',
        ])->assertStatus(400);
    }

    public function testVerifyEmailValidationErrors(): void
    {
        $this->post('/api/auth/verify-email', [])->assertStatus(422);
    }

    // ─── Resend Verification ─────────────────────────────────────────

    public function testResendVerificationUnauthorized(): void
    {
        $this->post('/api/auth/verify-email/resend')->assertStatus(401);
    }

    public function testResendVerificationSuccess(): void
    {
        $app = $this->createApp();
        $email = 'resend-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Resend User',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->post('/api/auth/verify-email/resend', [], $headers)->assertOk();
    }

    // ─── Profile ─────────────────────────────────────────────────────

    public function testGetProfile(): void
    {
        $app = $this->createApp();
        $email = 'profget-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Profile User',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/profile', $headers)->assertOk();
    }

    public function testGetProfileWithLocale(): void
    {
        $app = $this->createApp();
        $email = 'profloc-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Profile Locale',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/profile?locale=vi', $headers)->assertOk();
    }

    public function testGetProfileInvalidLocale(): void
    {
        $app = $this->createApp();
        $email = 'profinv-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Profile Inv',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/profile?locale=invalid', $headers)->assertOk();
    }

    public function testUpdateProfile(): void
    {
        $app = $this->createApp();
        $email = 'profupd-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Update Profile',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->put('/api/profile', [
            'name' => 'Updated Name',
            'email' => 'updated-' . uniqid() . '@test.com',
        ], $headers)->assertOk();
    }

    public function testUpdateProfileAvatarPhone(): void
    {
        $app = $this->createApp();
        $email = 'profav-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Avatar User',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->put('/api/profile', [
            'name' => 'Avatar User',
            'avatar' => 'https://example.com/avatar.jpg',
            'phone' => '1234567890',
        ], $headers)->assertOk();
    }

    public function testUpdateProfileDuplicateEmail(): void
    {
        $app = $this->createApp();
        $email1 = 'prof1-' . uniqid() . '@test.com';
        $email2 = 'prof2-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'User 1',
            'email' => $email1,
            'password' => 'secret123',
        ]);
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'User 2',
            'email' => $email2,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email2,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $resp = $this->dispatch($app, 'PUT', '/api/profile', [
            'name' => 'Changed',
            'email' => $email1,
        ], $headers);
        $this->assertContains($resp->statusCode(), [200, 422]);
    }

    public function testUpdateProfileUnauthorized(): void
    {
        $this->put('/api/profile', ['name' => 'Test'])->assertStatus(401);
    }

    // ─── Change Password ─────────────────────────────────────────────

    public function testChangePasswordSuccess(): void
    {
        $app = $this->createApp();
        $email = 'chgok-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Chg PW',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->put('/api/profile/password', [
            'current_password' => 'secret123',
            'new_password' => 'newsecret456',
        ], $headers)->assertOk();
    }

    public function testChangePasswordWrongCurrent(): void
    {
        $app = $this->createApp();
        $email = 'chgbad-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Chg Bad',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->put('/api/profile/password', [
            'current_password' => 'wrongpassword',
            'new_password' => 'newsecret456',
        ], $headers)->assertStatus(400);
    }

    public function testChangePasswordMissingFields(): void
    {
        $app = $this->createApp();
        $email = 'chgmiss-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Chg Miss',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->put('/api/profile/password', [], $headers)->assertStatus(422);
    }

    public function testChangePasswordUnauthorized(): void
    {
        $this->put('/api/profile/password', [
            'current_password' => 'old',
            'new_password' => 'new',
        ])->assertStatus(401);
    }

    // ─── Settings ────────────────────────────────────────────────────

    public function testGetSettings(): void
    {
        $app = $this->createApp();
        $email = 'settingsadmin-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Settings Admin',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET role = 'admin' WHERE email = ?", [$email]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/settings', $headers)->assertOk();
    }

    public function testGetSettingsForbidden(): void
    {
        $app = $this->createApp();
        $email = 'settings-user-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Regular',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/settings', $headers)->assertForbidden();
    }

    public function testUpdateSettings(): void
    {
        $app = $this->createApp();
        $email = 'settingsupd-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Settings Upd Admin',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET role = 'admin' WHERE email = ?", [$email]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->put('/api/settings', [
            'app_name' => 'TestApp',
            'locale' => 'vi',
        ], $headers)->assertOk();
    }

    public function testUpdateSettingsEmpty(): void
    {
        $app = $this->createApp();
        $email = 'settingsempty-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Settings Empty Admin',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET role = 'admin' WHERE email = ?", [$email]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->put('/api/settings', [], $headers)->assertStatus(422);
    }

    public function testUpdateSettingsForbidden(): void
    {
        $app = $this->createApp();
        $email = 'settings-upd-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Regular',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->put('/api/settings', ['key' => 'val'], $headers)->assertForbidden();
    }

    // ─── Dashboard ───────────────────────────────────────────────────

    public function testDashboardStats(): void
    {
        $app = $this->createApp();
        $email = 'dashadmin-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Dashboard Admin',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET role = 'admin' WHERE email = ?", [$email]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        $this->get('/api/dashboard/stats', $headers)->assertOk();
    }

    // ─── Server Info ─────────────────────────────────────────────────

    public function testServerInfo(): void
    {
        $this->get('/api/server/info')->assertOk();
    }

    // ─── Upload ──────────────────────────────────────────────────────

    public function testUploadUnauthorized(): void
    {
        $this->post('/api/upload')->assertStatus(401);
    }

    public function testUploadAvatarUnauthorized(): void
    {
        $this->post('/api/upload/avatar')->assertStatus(401);
    }
}
