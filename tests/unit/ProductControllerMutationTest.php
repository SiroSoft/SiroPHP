<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;

final class ProductControllerMutationTest extends TestCase
{
    private function auth(): array
    {
        $app = $this->createApp();
        $email = 'prodadmin-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Product Admin',
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
            \Siro\Core\Database::execute("DELETE FROM products");
            \Siro\Core\Database::execute("DELETE FROM categories");
        } catch (\Throwable) {
        }
    }

    // ─── Index ───────────────────────────────────────────────────────

    public function testIndexReturnsProducts(): void
    {
        $this->get('/api/products', $this->auth())->assertOk();
    }

    public function testIndexWithSearch(): void
    {
        $this->get('/api/products?search=test', $this->auth())->assertOk();
    }

    public function testIndexWithCategory(): void
    {
        $this->get('/api/products?category=electronics', $this->auth())->assertOk();
    }

    public function testIndexWithStatus(): void
    {
        $this->get('/api/products?status=active', $this->auth())->assertOk();
    }

    public function testIndexWithPriceRange(): void
    {
        $this->get('/api/products?price_min=10&price_max=100', $this->auth())->assertOk();
    }

    public function testIndexWithSort(): void
    {
        $this->get('/api/products?sort=price&order=asc', $this->auth())->assertOk();
    }

    public function testIndexWithInvalidSort(): void
    {
        $this->get('/api/products?sort=invalid_sort&order=desc', $this->auth())->assertOk();
    }

    public function testIndexWithInvalidOrder(): void
    {
        $this->get('/api/products?order=invalid', $this->auth())->assertOk();
    }

    public function testIndexPagination(): void
    {
        $this->get('/api/products?page=1&per_page=5', $this->auth())->assertOk();
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function testShowInvalidId(): void
    {
        $this->get('/api/products/0', $this->auth())->assertStatus(422);
    }

    public function testShowNonexistent(): void
    {
        $this->get('/api/products/99999', $this->auth())->assertNotFound();
    }

    public function testShowValidProduct(): void
    {
        $app = $this->createApp();
        $auth = $this->authenticate($app);
        $createResp = $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'Test Product',
            'price' => 29.99,
            'stock' => 10,
            'description' => 'A test product',
        ], $auth);
        $createJson = $this->responseJson($createResp);
        $id = $createJson['data']['id'] ?? 0;
        if ($id > 0) {
            $this->get("/api/products/$id", $auth)->assertOk();
        }
    }

    // ─── Store ───────────────────────────────────────────────────────

    public function testStoreCreatesProduct(): void
    {
        $this->post('/api/products', [
            'name' => 'New Product',
            'price' => 49.99,
            'stock' => 25,
        ], $this->auth())->assertCreated();
    }

    public function testStoreWithAllFields(): void
    {
        $this->post('/api/products', [
            'name' => 'Full Product',
            'description' => 'Full description',
            'price' => 99.99,
            'stock' => 50,
            'category' => 'Electronics',
            'status' => 'active',
            'cover_image' => 'https://example.com/cover.jpg',
            'short_description' => 'Short desc',
        ], $this->auth())->assertCreated();
    }

    public function testStoreWithIsFeatured(): void
    {
        $this->post('/api/products', [
            'name' => 'Featured Product',
            'price' => 199.99,
            'stock' => 10,
            'is_featured' => true,
        ], $this->auth())->assertCreated();
    }

    public function testStoreWithInactiveStatus(): void
    {
        $this->post('/api/products', [
            'name' => 'Inactive Product',
            'price' => 10.00,
            'stock' => 5,
            'is_active' => false,
        ], $this->auth())->assertCreated();
    }

    public function testStoreWithCategoryName(): void
    {
        $this->post('/api/products', [
            'name' => 'Category Product',
            'price' => 35.00,
            'stock' => 15,
            'category_name' => 'Books',
        ], $this->auth())->assertCreated();
    }

    public function testStoreValidationErrors(): void
    {
        $this->post('/api/products', [], $this->auth())->assertStatus(422);
    }

    public function testStoreForbiddenForNonAdmin(): void
    {
        $app = $this->createApp();
        $email = 'nonadmin-' . uniqid() . '@test.com';
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
        $this->post('/api/products', ['name' => 'Nope', 'price' => 10, 'stock' => 1], $headers)->assertForbidden();
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function testUpdateInvalidId(): void
    {
        $this->put('/api/products/0', ['name' => 'Test'], $this->auth())->assertStatus(422);
    }

    public function testUpdateNonexistent(): void
    {
        $this->put('/api/products/99999', ['name' => 'Test'], $this->auth())->assertNotFound();
    }

    public function testUpdateValidProduct(): void
    {
        $app = $this->createApp();
        $auth = $this->authenticate($app);
        $createResp = $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'Update Product',
            'price' => 20.00,
            'stock' => 10,
        ], $auth);
        $createJson = $this->responseJson($createResp);
        $id = $createJson['data']['id'] ?? 0;
        if ($id > 0) {
            $this->put("/api/products/$id", [
                'name' => 'Updated Product',
                'price' => 25.00,
                'stock' => 15,
                'cover_image' => 'https://example.com/new.jpg',
            ], $auth)->assertOk();
        }
    }

    public function testUpdateWithIsFeatured(): void
    {
        $app = $this->createApp();
        $auth = $this->authenticate($app);
        $createResp = $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'Feat Product',
            'price' => 30.00,
            'stock' => 5,
        ], $auth);
        $createJson = $this->responseJson($createResp);
        $id = $createJson['data']['id'] ?? 0;
        if ($id > 0) {
            $this->put("/api/products/$id", [
                'name' => 'Feat Updated',
                'is_featured' => true,
                'category_name' => 'Tech',
            ], $auth)->assertOk();
        }
    }

    public function testUpdateForbiddenForNonAdmin(): void
    {
        $app = $this->createApp();
        $email = 'nonadmin-up-' . uniqid() . '@test.com';
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
        $this->put('/api/products/1', ['name' => 'Nope'], $headers)->assertForbidden();
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function testDeleteInvalidId(): void
    {
        $this->delete('/api/products/0', $this->auth())->assertStatus(422);
    }

    public function testDeleteNonexistent(): void
    {
        $this->delete('/api/products/99999', $this->auth())->assertNotFound();
    }

    public function testDeleteValidProduct(): void
    {
        $app = $this->createApp();
        $auth = $this->authenticate($app);
        $createResp = $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'Delete Product',
            'price' => 15.00,
            'stock' => 3,
        ], $auth);
        $createJson = $this->responseJson($createResp);
        $id = $createJson['data']['id'] ?? 0;
        if ($id > 0) {
            $this->delete("/api/products/$id", $auth)->assertNoContent();
        }
    }

    public function testDeleteForbiddenForNonAdmin(): void
    {
        $app = $this->createApp();
        $email = 'nonadmin-del-' . uniqid() . '@test.com';
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
        $this->delete('/api/products/1', $headers)->assertForbidden();
    }
}
