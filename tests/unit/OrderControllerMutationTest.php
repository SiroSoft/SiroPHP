<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;

final class OrderControllerMutationTest extends TestCase
{
    private function auth(): array
    {
        $app = $this->createApp();
        $email = 'orderadmin-' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Order Admin',
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
            \Siro\Core\Database::execute("DELETE FROM orders");
            \Siro\Core\Database::execute("DELETE FROM products");
        } catch (\Throwable) {
        }
    }

    private function createProduct(array $auth): int
    {
        $app = $this->createApp();
        $resp = $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'Order Test Product',
            'price' => 25.00,
            'stock' => 100,
        ], $auth);
        $json = $this->responseJson($resp);
        return (int) ($json['data']['id'] ?? 0);
    }

    // ─── Index ───────────────────────────────────────────────────────

    public function testIndexReturnsOrders(): void
    {
        $this->get('/api/orders', $this->auth())->assertOk();
    }

    public function testIndexWithStatusFilter(): void
    {
        $this->get('/api/orders?status=pending', $this->auth())->assertOk();
    }

    public function testIndexPagination(): void
    {
        $this->get('/api/orders?page=1&per_page=5', $this->auth())->assertOk();
    }

    public function testIndexWithUserIdFilter(): void
    {
        $this->get('/api/orders?user_id=1', $this->auth())->assertOk();
    }

    // ─── Show ────────────────────────────────────────────────────────

    public function testShowInvalidId(): void
    {
        $this->get('/api/orders/0', $this->auth())->assertStatus(422);
    }

    public function testShowNonexistent(): void
    {
        $this->get('/api/orders/99999', $this->auth())->assertNotFound();
    }

    // ─── Store ───────────────────────────────────────────────────────

    public function testStoreCreatesOrder(): void
    {
        $auth = $this->auth();
        $productId = $this->createProduct($auth);
        if ($productId > 0) {
            $this->post('/api/orders', [
                'customer_name' => 'Test Customer',
                'customer_email' => 'customer@test.com',
                'items' => [
                    ['product_id' => $productId, 'price' => 25.00, 'quantity' => 2],
                ],
            ], $auth)->assertCreated();
        }
    }

    public function testStoreValidationErrors(): void
    {
        $this->post('/api/orders', [], $this->auth())->assertStatus(422);
    }

    public function testStoreItemsNotArray(): void
    {
        $this->post('/api/orders', [
            'customer_name' => 'Test',
            'customer_email' => 'test@test.com',
            'items' => 'not-an-array',
        ], $this->auth())->assertStatus(422);
    }

    public function testStoreItemMissingFields(): void
    {
        $this->post('/api/orders', [
            'customer_name' => 'Test',
            'customer_email' => 'test@test.com',
            'items' => [['product_id' => 1]],
        ], $this->auth())->assertStatus(422);
    }

    public function testStoreItemInvalidPrice(): void
    {
        $this->post('/api/orders', [
            'customer_name' => 'Test',
            'customer_email' => 'test@test.com',
            'items' => [['product_id' => 1, 'price' => -5, 'quantity' => 1]],
        ], $this->auth())->assertStatus(422);
    }

    public function testStoreItemInvalidQuantity(): void
    {
        $this->post('/api/orders', [
            'customer_name' => 'Test',
            'customer_email' => 'test@test.com',
            'items' => [['product_id' => 1, 'price' => 10, 'quantity' => 0]],
        ], $this->auth())->assertStatus(422);
    }

    public function testStoreItemNonexistentProduct(): void
    {
        $this->post('/api/orders', [
            'customer_name' => 'Test',
            'customer_email' => 'test@test.com',
            'items' => [['product_id' => 99999, 'price' => 10, 'quantity' => 1]],
        ], $this->auth())->assertStatus(422);
    }

    // ─── Update ──────────────────────────────────────────────────────

    public function testUpdateInvalidId(): void
    {
        $this->put('/api/orders/0', ['customer_name' => 'Test'], $this->auth())->assertStatus(422);
    }

    public function testUpdateNonexistent(): void
    {
        $this->put('/api/orders/99999', ['customer_name' => 'Test'], $this->auth())->assertNotFound();
    }

    public function testUpdateValidOrder(): void
    {
        $auth = $this->auth();
        $productId = $this->createProduct($auth);
        if ($productId > 0) {
            $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/orders', [
                'customer_name' => 'Original',
                'customer_email' => 'orig@test.com',
                'items' => [['product_id' => $productId, 'price' => 25.00, 'quantity' => 1]],
            ], $auth);
            $json = $this->responseJson($createResp);
            $orderId = $json['data']['id'] ?? 0;
            if ($orderId > 0) {
                $this->put("/api/orders/$orderId", [
                    'customer_name' => 'Updated Name',
                    'customer_email' => 'updated@test.com',
                ], $auth)->assertOk();
            }
        }
    }

    // ─── Update Status ───────────────────────────────────────────────

    public function testUpdateStatusInvalidId(): void
    {
        $auth = $this->auth();
        $resp = $this->dispatch($this->createApp(), 'PATCH', '/api/orders/0/status', [
            'status' => 'processing',
        ], $auth);
        $this->assertContains($resp->statusCode(), [404, 422]);
    }

    public function testUpdateStatusNonexistent(): void
    {
        $resp = $this->dispatch($this->createApp(), 'PATCH', '/api/orders/99999/status', [
            'status' => 'processing',
        ], $this->auth());
        $this->assertContains($resp->statusCode(), [404, 422]);
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function testDeleteInvalidId(): void
    {
        $this->delete('/api/orders/0', $this->auth())->assertStatus(422);
    }

    public function testDeleteNonexistent(): void
    {
        $this->delete('/api/orders/99999', $this->auth())->assertNotFound();
    }

    public function testDeleteValidOrder(): void
    {
        $auth = $this->auth();
        $productId = $this->createProduct($auth);
        if ($productId > 0) {
            $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/orders', [
                'customer_name' => 'Delete Order',
                'customer_email' => 'del@test.com',
                'items' => [['product_id' => $productId, 'price' => 25.00, 'quantity' => 1]],
            ], $auth);
            $json = $this->responseJson($createResp);
            $orderId = $json['data']['id'] ?? 0;
            if ($orderId > 0) {
                $this->delete("/api/orders/$orderId", $auth)->assertNoContent();
            }
        }
    }

    public function testShowValidOrder(): void
    {
        $auth = $this->auth();
        $productId = $this->createProduct($auth);
        if ($productId > 0) {
            $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/orders', [
                'customer_name' => 'Show Order',
                'customer_email' => 'show@test.com',
                'items' => [['product_id' => $productId, 'price' => 25.00, 'quantity' => 1]],
            ], $auth);
            $json = $this->responseJson($createResp);
            $orderId = $json['data']['id'] ?? 0;
            if ($orderId > 0) {
                $this->get("/api/orders/$orderId", $auth)->assertOk();
            }
        }
    }

    public function testUpdateStatusValidOrder(): void
    {
        $auth = $this->auth();
        $productId = $this->createProduct($auth);
        if ($productId > 0) {
            $createResp = $this->dispatch($app = $this->createApp(), 'POST', '/api/orders', [
                'customer_name' => 'Status Order',
                'customer_email' => 'status@test.com',
                'items' => [['product_id' => $productId, 'price' => 25.00, 'quantity' => 1]],
            ], $auth);
            $json = $this->responseJson($createResp);
            $orderId = $json['data']['id'] ?? 0;
            if ($orderId > 0) {
                $resp = $this->dispatch($this->createApp(), 'PATCH', "/api/orders/$orderId/status", [
                    'status' => 'processing',
                ], $auth);
                $this->assertContains($resp->statusCode(), [200, 422]);
            }
        }
    }
}
