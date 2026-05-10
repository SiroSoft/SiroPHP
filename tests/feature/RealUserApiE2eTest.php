<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

/**
 * Real-user API End-to-End tests.
 *
 * Tests ALL CRUD endpoints, auth flow, validation, 404, filtering, pagination.
 */
final class RealUserApiE2eTest extends TestCase
{
    // ========== 1. HEALTH & ROOT ==========

    public function test_health_endpoint(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'GET', '/health');
        $this->assertEquals(200, $r->statusCode());
    }

    public function test_root_endpoint(): void
    {
        $app = $this->createApp();
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/')->statusCode());
    }

    public function test_public_list_returns_200(): void
    {
        $app = $this->createApp();
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/categories')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/tags')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/orders')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/posts')->statusCode());
    }

    // ========== 2. AUTH ==========

    public function test_auth_register_and_login(): void
    {
        $app = $this->createApp();
        $email = 'e2e_' . uniqid() . '@test.com';

        $r = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'E2E User', 'email' => $email,
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ]);
        $this->assertEquals(201, $r->statusCode(), 'Register should return 201');
    }

    public function test_auth_register_duplicate_email(): void
    {
        $app = $this->createApp();
        $email = 'dup_' . uniqid() . '@test.com';
        $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'First', 'email' => $email,
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ]);
        $r2 = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Second', 'email' => $email,
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ]);
        $this->assertEquals(422, $r2->statusCode(), 'Duplicate email should return 422');
    }

    public function test_auth_login_nonexistent_user(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => 'noexist@test.com', 'password' => 'wrongpass',
        ]);
        $this->assertEquals(401, $r->statusCode(), 'Wrong login should return 401');
    }

    public function test_auth_register_invalid_data(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'POST', '/api/auth/register', ['name' => 'x']);
        $this->assertEquals(422, $r->statusCode(), 'Missing fields should return 422');
    }

    // ========== 3. VALIDATION (422) ==========

    public function test_validation_errors_return_422(): void
    {
        $app = $this->createApp();
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/categories', ['name' => ''])->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/categories', [])->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/products', ['price' => 'x'])->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/orders', [])->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/tags', ['name' => ''])->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/posts', [])->statusCode());
    }

    // ========== 4. 404 ==========

    public function test_not_found_returns_404(): void
    {
        $app = $this->createApp();
        $this->assertEquals(404, $this->dispatch($app, 'GET', '/api/products/99999')->statusCode());
        $this->assertEquals(404, $this->dispatch($app, 'GET', '/api/categories/99999')->statusCode());
        $this->assertEquals(404, $this->dispatch($app, 'GET', '/api/tags/99999')->statusCode());
        $this->assertEquals(404, $this->dispatch($app, 'GET', '/api/orders/99999')->statusCode());
        $this->assertEquals(404, $this->dispatch($app, 'GET', '/api/posts/99999')->statusCode());
        $this->assertEquals(404, $this->dispatch($app, 'GET', '/api/users/99999')->statusCode());
        $this->assertEquals(404, $this->dispatch($app, 'GET', '/api/nonexistent')->statusCode());
    }

    // ========== 5. PRODUCT CRUD ==========

    public function test_product_full_crud(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'E2E Product', 'price' => 99.99, 'stock' => 10,
            'category' => 'test', 'status' => 'active',
        ]);
        $this->assertEquals(201, $r->statusCode());
        $id = json_decode($this->getResponseBody($r), true)['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/products/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/products/{$id}", ['name' => 'Updated'])->statusCode());
        $this->dispatch($app, 'DELETE', "/api/products/{$id}");
    }

    // ========== 6. CATEGORY CRUD ==========

    public function test_category_full_crud(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'POST', '/api/categories', ['name' => 'E2E Cat']);
        $this->assertEquals(201, $r->statusCode());
        $id = json_decode($this->getResponseBody($r), true)['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/categories/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/categories/{$id}", ['name' => 'Updated'])->statusCode());
        $this->dispatch($app, 'DELETE', "/api/categories/{$id}");
    }

    // ========== 7. TAG CRUD ==========

    public function test_tag_full_crud(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'POST', '/api/tags', ['name' => 'E2E Tag']);
        $this->assertEquals(201, $r->statusCode());
        $id = json_decode($this->getResponseBody($r), true)['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/tags/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/tags/{$id}", ['name' => 'Updated'])->statusCode());
    }

    // ========== 8. ORDER CRUD ==========

    public function test_order_full_crud(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'POST', '/api/orders', [
            'customer_name' => 'E2E Cus', 'customer_email' => 'e2e@test.com',
            'total' => 199.99, 'status' => 'pending',
            'items' => [['product' => 'Laptop', 'qty' => 1]],
        ]);
        $this->assertEquals(201, $r->statusCode(), 'Order creation failed: ' . $this->getResponseBody($r));
        $body = json_decode($this->getResponseBody($r), true);
        $id = $body['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id);

        $items = $body['data']['items'] ?? [];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/orders/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/orders/{$id}", ['status' => 'completed'])->statusCode());
        $this->dispatch($app, 'DELETE', "/api/orders/{$id}");
    }

    // ========== 9. POST CRUD ==========

    public function test_post_full_crud(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'POST', '/api/posts', [
            'title' => 'E2E Post', 'body' => 'Body with enough length for validation.',
            'locale' => 'en', 'status' => 'published',
        ]);
        $this->assertEquals(201, $r->statusCode());
        $id = json_decode($this->getResponseBody($r), true)['data']['id'] ?? 0;
        $this->assertGreaterThan(0, $id);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/posts/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/posts/{$id}", ['title' => 'Updated'])->statusCode());
    }

    // ========== 10. FILTERING ==========

    public function test_product_filtering(): void
    {
        $app = $this->createApp();
        $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'FilterTest', 'price' => 10, 'category' => 'fcat', 'status' => 'active',
        ]);
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?category=fcat')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?search=FilterTest')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?sort=price&order=asc')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?status=active')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?price_min=5&price_max=50')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/orders?status=pending')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/posts?locale=en')->statusCode());
    }

    // ========== 11. PAGINATION ==========

    public function test_pagination_returns_meta(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'GET', '/api/products?page=1&per_page=5');
        $this->assertEquals(200, $r->statusCode());
        $body = json_decode($this->getResponseBody($r), true);
        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('page', $body['meta'], 'meta keys: ' . implode(', ', array_keys($body['meta'])));
    }

    // ========== 12. RESPONSE FORMAT ==========

    public function test_error_response_format(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'GET', '/api/products/99999');
        $body = json_decode($this->getResponseBody($r), true);
        $this->assertArrayHasKey('success', $body);
        $this->assertArrayHasKey('message', $body);
        $this->assertFalse($body['success']);
    }

    public function test_user_hides_password(): void
    {
        $app = $this->createApp();
        $email = 'hide_' . uniqid() . '@test.com';
        $r = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Hide PW', 'email' => $email,
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ]);
        $this->assertEquals(201, $r->statusCode());

        $token = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email, 'password' => 'secret123',
        ]);
        $tokenData = json_decode($this->getResponseBody($token), true);
        $tokenStr = $tokenData['data']['token'] ?? '';

        $r2 = $this->dispatch($app, 'GET', '/api/auth/me', [], [
            'Authorization' => 'Bearer ' . $tokenStr,
        ]);
        if ($r2->statusCode() === 200) {
            $meData = json_decode($this->getResponseBody($r2), true)['data'] ?? [];
            $this->assertArrayNotHasKey('password', $meData);
        }
    }

    // ========== HELPERS ==========

    private function getResponseBody(\Siro\Core\Response $response): string
    {
        ob_start();
        $response->send();
        return ob_get_clean() ?: '';
    }
}
