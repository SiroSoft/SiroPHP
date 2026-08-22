<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;

final class UserControllerMutationTest extends TestCase
{
    private array $adminAuth = [];

    private function adminAuth(): array
    {
        if ($this->adminAuth === []) {
            $app = $this->createApp();
            $email = 'admin-' . uniqid() . '@test.com';
            $this->dispatch($app, 'POST', '/api/auth/register', [
                'name' => 'Admin User',
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
            $this->adminAuth = [
                'authorization' => 'Bearer ' . $token,
                'content-type' => 'application/json',
            ];
        }
        return $this->adminAuth;
    }

    private function userAuth(): array
    {
        $app = $this->createApp();
        $email = 'user-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Regular User',
            'email' => $email,
            'password' => 'secret123',
        ]);
        \Siro\Core\Database::execute("UPDATE users SET role = 'user' WHERE email = ?", [$email]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $token = $loginJson['data']['token'] ?? '';
        return [
            'authorization' => 'Bearer ' . $token,
            'content-type' => 'application/json',
        ];
    }

    // ─── Index ───────────────────────────────────────────────────────

    public function testIndexReturnsUsersList(): void
    {
        $this->get('/api/users', $this->adminAuth())->assertOk();
    }

    public function testIndexForbiddenForNonAdmin(): void
    {
        $this->get('/api/users', $this->userAuth())->assertForbidden();
    }

    public function testIndexWithStatusFilter(): void
    {
        $this->get('/api/users?status=active', $this->adminAuth())->assertOk();
    }

    public function testIndexWithInactiveStatus(): void
    {
        $this->get('/api/users?status=inactive', $this->adminAuth())->assertOk();
    }

    public function testIndexWithSuspendedStatus(): void
    {
        $this->get('/api/users?status=suspended', $this->adminAuth())->assertOk();
    }

    public function testIndexWithRoleFilter(): void
    {
        $this->get('/api/users?role=admin', $this->adminAuth())->assertOk();
    }

    public function testIndexWithPagination(): void
    {
        $this->get('/api/users?page=1&per_page=5', $this->adminAuth())->assertOk();
    }

    public function testIndexWithHighPerPage(): void
    {
        $resp = $this->get('/api/users?per_page=200', $this->adminAuth());
        $this->assertContains($resp->status(), [200, 422]);
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function testShowOwnProfile(): void
    {
        $app = $this->createApp();
        $email = 'own-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Own Profile',
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $loginJson = $this->responseJson($login);
        $userId = $loginJson['data']['user']['id'] ?? 0;
        $token = $loginJson['data']['token'] ?? '';
        $headers = ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
        if ($userId > 0) {
            $this->get("/api/users/$userId", $headers)->assertOk();
        }
    }

    public function testShowInvalidId(): void
    {
        $this->get('/api/users/0', $this->adminAuth())->assertStatus(422);
    }

    public function testShowNonexistentUser(): void
    {
        $this->get('/api/users/99999', $this->adminAuth())->assertNotFound();
    }

    public function testShowOtherUserForbidden(): void
    {
        $userAuth = $this->userAuth();
        $this->get('/api/users/1', $userAuth)->assertForbidden();
    }

    // ─── Store ───────────────────────────────────────────────────────

    public function testStoreAsAdmin(): void
    {
        $this->post('/api/users', [
            'name' => 'New Admin User',
            'email' => 'admin-' . uniqid() . '@test.com',
            'password' => 'secret123',
        ], $this->adminAuth())->assertCreated();
    }

    public function testStoreForbiddenForNonAdmin(): void
    {
        $this->post('/api/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'secret123',
        ], $this->userAuth())->assertForbidden();
    }

    public function testStoreWithRoleAndStatus(): void
    {
        $this->post('/api/users', [
            'name' => 'Role Test User',
            'email' => 'role-' . uniqid() . '@test.com',
            'password' => 'secret123',
            'role' => 'admin',
            'status' => 'active',
            'avatar' => 'https://example.com/avatar.jpg',
            'phone' => '1234567890',
        ], $this->adminAuth())->assertCreated();
    }

    public function testStoreWithInactiveStatus(): void
    {
        $this->post('/api/users', [
            'name' => 'Inactive User',
            'email' => 'inactive-' . uniqid() . '@test.com',
            'password' => 'secret123',
            'status' => 'inactive',
        ], $this->adminAuth())->assertCreated();
    }

    public function testStoreWithSuspendedStatus(): void
    {
        $this->post('/api/users', [
            'name' => 'Suspended User',
            'email' => 'suspended-' . uniqid() . '@test.com',
            'password' => 'secret123',
            'status' => 'suspended',
        ], $this->adminAuth())->assertCreated();
    }

    public function testStoreDuplicateEmail(): void
    {
        $email = 'dup-' . uniqid() . '@test.com';
        $this->post('/api/users', [
            'name' => 'First',
            'email' => $email,
            'password' => 'secret123',
        ], $this->adminAuth())->assertCreated();
        $this->post('/api/users', [
            'name' => 'Second',
            'email' => $email,
            'password' => 'secret123',
        ], $this->adminAuth())->assertStatus(422);
    }

    public function testStoreValidationErrors(): void
    {
        $this->post('/api/users', [
            'name' => '',
            'email' => 'invalid',
            'password' => 'short',
        ], $this->adminAuth())->assertStatus(422);
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function testUpdateForbiddenForNonAdmin(): void
    {
        $userAuth = $this->userAuth();
        $this->put('/api/users/1', ['name' => 'Hacked'], $userAuth)->assertForbidden();
    }

    public function testUpdateInvalidId(): void
    {
        $this->put('/api/users/0', ['name' => 'Test'], $this->adminAuth())->assertStatus(404);
    }

    public function testUpdateNonexistent(): void
    {
        $this->put('/api/users/99999', ['name' => 'Test'], $this->adminAuth())->assertNotFound();
    }

    public function testUpdateByNameAsAdmin(): void
    {
        $app = $this->createApp();
        $auth = $this->adminAuth();
        $reg = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Update Me',
            'email' => 'update-' . uniqid() . '@test.com',
            'password' => 'secret123',
        ]);
        $regJson = $this->responseJson($reg);
        $userId = $regJson['data']['user']['id'] ?? 0;
        if ($userId > 0) {
            $this->put("/api/users/$userId", ['name' => 'Updated Name'], $auth)->assertOk();
        }
    }

    public function testUpdateWithAvatarAndPhone(): void
    {
        $app = $this->createApp();
        $auth = $this->adminAuth();
        $reg = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Avatar User',
            'email' => 'avatar-' . uniqid() . '@test.com',
            'password' => 'secret123',
        ]);
        $regJson = $this->responseJson($reg);
        $userId = $regJson['data']['user']['id'] ?? 0;
        if ($userId > 0) {
            $this->put("/api/users/$userId", [
                'name' => 'Avatar Updated',
                'avatar' => 'https://example.com/new.jpg',
                'phone' => '9876543210',
            ], $auth)->assertOk();
        }
    }

    public function testUpdateWithStatusChange(): void
    {
        $app = $this->createApp();
        $auth = $this->adminAuth();
        $reg = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Status User',
            'email' => 'status-' . uniqid() . '@test.com',
            'password' => 'secret123',
        ]);
        $regJson = $this->responseJson($reg);
        $userId = $regJson['data']['user']['id'] ?? 0;
        if ($userId > 0) {
            $this->put("/api/users/$userId", [
                'name' => 'Status Changed',
                'status' => 'inactive',
            ], $auth)->assertOk();
        }
    }

    public function testUpdateDuplicateEmail(): void
    {
        $app = $this->createApp();
        $auth = $this->adminAuth();
        $email1 = 'dup1-' . uniqid() . '@test.com';
        $email2 = 'dup2-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'User 1',
            'email' => $email1,
            'password' => 'secret123',
        ]);
        $reg2 = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'User 2',
            'email' => $email2,
            'password' => 'secret123',
        ]);
        $reg2Json = $this->responseJson($reg2);
        $userId2 = $reg2Json['data']['user']['id'] ?? 0;
        if ($userId2 > 0) {
            $resp = $this->put("/api/users/$userId2", [
                'name' => 'Changed',
                'email' => $email1,
            ], $auth);
            $this->assertContains($resp->status(), [200, 422]);
        }
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function testDeleteForbiddenForNonAdmin(): void
    {
        $userAuth = $this->userAuth();
        $this->delete('/api/users/1', $userAuth)->assertForbidden();
    }

    public function testDeleteInvalidId(): void
    {
        $this->delete('/api/users/0', $this->adminAuth())->assertStatus(404);
    }

    public function testDeleteNonexistent(): void
    {
        $this->delete('/api/users/99999', $this->adminAuth())->assertNotFound();
    }

    public function testDeleteAsAdmin(): void
    {
        $app = $this->createApp();
        $auth = $this->adminAuth();
        $reg = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Delete Me',
            'email' => 'delete-' . uniqid() . '@test.com',
            'password' => 'secret123',
        ]);
        $regJson = $this->responseJson($reg);
        $userId = $regJson['data']['user']['id'] ?? 0;
        if ($userId > 0) {
            $this->delete("/api/users/$userId", $auth)->assertNoContent();
        }
    }
}
