<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Role;
use App\Tests\TestCase;

final class TagControllerMutationTest extends TestCase
{
    private function auth(): array
    {
        $app = $this->createApp();
        $email = 'tagadmin-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Tag Admin',
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
        return ['authorization' => 'Bearer ' . $token, 'content-type' => 'application/json'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        try {
            \Siro\Core\Database::execute("DELETE FROM tags");
        } catch (\Throwable) {
        }
    }

    // ─── Index ───────────────────────────────────────────────────────

    public function testIndexReturnsTags(): void
    {
        $this->get('/api/tags', $this->auth())->assertOk();
    }

    public function testIndexPagination(): void
    {
        $this->get('/api/tags?page=1&per_page=5', $this->auth())->assertOk();
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function testShowInvalidId(): void
    {
        $this->get('/api/tags/0', $this->auth())->assertStatus(422);
    }

    public function testShowNonexistent(): void
    {
        $this->get('/api/tags/99999', $this->auth())->assertNotFound();
    }

    public function testShowValidTag(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/tags', [
            'name' => 'Test Tag',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->get("/api/tags/$id", $auth)->assertOk();
        }
    }

    // ─── Store ───────────────────────────────────────────────────────

    public function testStoreCreatesTag(): void
    {
        $this->post('/api/tags', [
            'name' => 'New Tag',
        ], $this->auth())->assertCreated();
    }

    public function testStoreValidationErrors(): void
    {
        $this->post('/api/tags', [], $this->auth())->assertStatus(422);
    }

    public function testStoreForbiddenForNonAdmin(): void
    {
        $user = $this->registerUser('tag-user-');
        $this->post('/api/tags', ['name' => 'Nope'], $user['headers'])->assertForbidden();
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function testUpdateInvalidId(): void
    {
        $this->put('/api/tags/0', ['name' => 'Test'], $this->auth())->assertStatus(422);
    }

    public function testUpdateNonexistent(): void
    {
        $this->put('/api/tags/99999', ['name' => 'Test'], $this->auth())->assertNotFound();
    }

    public function testUpdateValidTag(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/tags', [
            'name' => 'Update Me',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->put("/api/tags/$id", [
                'name' => 'Updated Tag',
            ], $auth)->assertOk();
        }
    }

    public function testUpdateForbiddenForNonAdmin(): void
    {
        $admin = $this->registerUser('tag-admin-', Role::ADMIN);
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/tags', [
            'name' => 'Target Tag',
        ], $admin['headers']);
        $id = $this->responseJson($createResp)['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id, 'Failed to create target tag.');
        $user = $this->registerUser('tag-upd-');
        $this->put("/api/tags/$id", ['name' => 'Nope'], $user['headers'])->assertForbidden();
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function testDeleteInvalidId(): void
    {
        $this->delete('/api/tags/0', $this->auth())->assertStatus(422);
    }

    public function testDeleteNonexistent(): void
    {
        $this->delete('/api/tags/99999', $this->auth())->assertNotFound();
    }

    public function testDeleteValidTag(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/tags', [
            'name' => 'Delete Me',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->delete("/api/tags/$id", $auth)->assertNoContent();
        }
    }

    public function testDeleteForbiddenForNonAdmin(): void
    {
        $admin = $this->registerUser('tag-admin-', Role::ADMIN);
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/tags', [
            'name' => 'Target Tag',
        ], $admin['headers']);
        $id = $this->responseJson($createResp)['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id, 'Failed to create target tag.');
        $user = $this->registerUser('tag-del-');
        $this->delete("/api/tags/$id", $user['headers'])->assertForbidden();
    }
}
