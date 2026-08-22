<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;

final class CategoryControllerMutationTest extends TestCase
{
    private function auth(): array
    {
        $app = $this->createApp();
        $email = 'catadmin-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Category Admin',
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
            \Siro\Core\Database::execute("DELETE FROM categories");
        } catch (\Throwable) {
        }
    }

    // ─── Index ───────────────────────────────────────────────────────

    public function testIndexReturnsCategories(): void
    {
        $this->get('/api/categories', $this->auth())->assertOk();
    }

    public function testIndexPagination(): void
    {
        $this->get('/api/categories?page=1&per_page=5', $this->auth())->assertOk();
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function testShowInvalidId(): void
    {
        $this->get('/api/categories/0', $this->auth())->assertStatus(422);
    }

    public function testShowNonexistent(): void
    {
        $this->get('/api/categories/99999', $this->auth())->assertNotFound();
    }

    public function testShowValidCategory(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/categories', [
            'name' => 'Test Category',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->get("/api/categories/$id", $auth)->assertOk();
        }
    }

    // ─── Store ───────────────────────────────────────────────────────

    public function testStoreCreatesCategory(): void
    {
        $this->post('/api/categories', [
            'name' => 'New Category',
        ], $this->auth())->assertCreated();
    }

    public function testStoreWithAllFields(): void
    {
        $this->post('/api/categories', [
            'name' => 'Full Category',
            'is_active' => true,
            'color' => '#FF0000',
            'description' => 'A description',
            'sort_order' => 5,
            'parent_id' => 1,
        ], $this->auth())->assertCreated();
    }

    public function testStoreInactive(): void
    {
        $this->post('/api/categories', [
            'name' => 'Inactive Category',
            'is_active' => false,
        ], $this->auth())->assertCreated();
    }

    public function testStoreInvalidParentId(): void
    {
        $this->post('/api/categories', [
            'name' => 'Bad Parent',
            'parent_id' => 'not-a-number',
        ], $this->auth())->assertCreated();
    }

    public function testStoreInvalidSortOrder(): void
    {
        $this->post('/api/categories', [
            'name' => 'Bad Sort',
            'sort_order' => 'not-a-number',
        ], $this->auth())->assertCreated();
    }

    public function testStoreValidationErrors(): void
    {
        $this->post('/api/categories', [], $this->auth())->assertStatus(422);
    }

    public function testStoreForbiddenForNonAdmin(): void
    {
        $app = $this->createApp();
        $email = 'cat-user-' . uniqid() . '@test.com';
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
        $this->post('/api/categories', ['name' => 'Nope'], $headers)->assertForbidden();
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function testUpdateInvalidId(): void
    {
        $this->put('/api/categories/0', ['name' => 'Test'], $this->auth())->assertStatus(422);
    }

    public function testUpdateNonexistent(): void
    {
        $this->put('/api/categories/99999', ['name' => 'Test'], $this->auth())->assertNotFound();
    }

    public function testUpdateValidCategory(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/categories', [
            'name' => 'Update Me',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->put("/api/categories/$id", [
                'name' => 'Updated Category',
                'is_active' => false,
                'color' => '#00FF00',
                'description' => 'Updated desc',
                'sort_order' => 10,
                'parent_id' => 1,
            ], $auth)->assertOk();
        }
    }

    public function testUpdateForbiddenForNonAdmin(): void
    {
        $app = $this->createApp();
        $email = 'cat-upd-' . uniqid() . '@test.com';
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
        $this->put('/api/categories/1', ['name' => 'Nope'], $headers)->assertForbidden();
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function testDeleteInvalidId(): void
    {
        $this->delete('/api/categories/0', $this->auth())->assertStatus(422);
    }

    public function testDeleteNonexistent(): void
    {
        $this->delete('/api/categories/99999', $this->auth())->assertNotFound();
    }

    public function testDeleteValidCategory(): void
    {
        $auth = $this->auth();
        $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/categories', [
            'name' => 'Delete Me',
        ], $auth);
        $json = $this->responseJson($createResp);
        $id = $json['data']['id'] ?? 0;
        if ($id > 0) {
            $this->delete("/api/categories/$id", $auth)->assertNoContent();
        }
    }

    public function testDeleteForbiddenForNonAdmin(): void
    {
        $app = $this->createApp();
        $email = 'cat-del-' . uniqid() . '@test.com';
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
        $this->delete('/api/categories/1', $headers)->assertForbidden();
    }
}
