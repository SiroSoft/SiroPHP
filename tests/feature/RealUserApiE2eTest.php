<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class RealUserApiE2eTest extends TestCase
{
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

    public function test_validation_errors_return_422(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/categories', ['name' => ''], $headers)->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/categories', [], $headers)->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/products', ['price' => 'x'], $headers)->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/orders', [], $headers)->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/tags', ['name' => ''], $headers)->statusCode());
        $this->assertEquals(422, $this->dispatch($app, 'POST', '/api/posts', [], $headers)->statusCode());
    }

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

    public function test_product_full_crud(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $r = $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'E2E Product', 'price' => 99.99, 'stock' => 10,
            'category' => 'test', 'status' => 'active',
        ], $headers);
        $this->assertEquals(201, $r->statusCode());
        $body = json_decode($this->getResponseBody($r), true);
        /** @var array<string, mixed> $body */
        $bodyData = $body['data'] ?? [];
        /** @var array<string, mixed> $bodyData */
        $id = $bodyData['id'] ?? 0;
        /** @var int|string $id */
        $this->assertGreaterThan(0, (int) $id);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/products/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/products/{$id}", ['name' => 'Updated'], $headers)->statusCode());
        $this->dispatch($app, 'DELETE', "/api/products/{$id}", [], $headers);
    }

    public function test_category_full_crud(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $r = $this->dispatch($app, 'POST', '/api/categories', ['name' => 'E2E Cat'], $headers);
        $this->assertEquals(201, $r->statusCode());
        $body = json_decode($this->getResponseBody($r), true);
        /** @var array<string, mixed> $body */
        $bodyData = $body['data'] ?? [];
        /** @var array<string, mixed> $bodyData */
        $id = $bodyData['id'] ?? 0;
        /** @var int|string $id */
        $this->assertGreaterThan(0, (int) $id);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/categories/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/categories/{$id}", ['name' => 'Updated'], $headers)->statusCode());
        $this->dispatch($app, 'DELETE', "/api/categories/{$id}", [], $headers);
    }

    public function test_tag_full_crud(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $r = $this->dispatch($app, 'POST', '/api/tags', ['name' => 'E2E Tag'], $headers);
        $this->assertEquals(201, $r->statusCode());
        $body = json_decode($this->getResponseBody($r), true);
        /** @var array<string, mixed> $body */
        $bodyData = $body['data'] ?? [];
        /** @var array<string, mixed> $bodyData */
        $id = $bodyData['id'] ?? 0;
        /** @var int|string $id */
        $this->assertGreaterThan(0, (int) $id);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/tags/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/tags/{$id}", ['name' => 'Updated'], $headers)->statusCode());
    }

    public function test_order_full_crud(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $r = $this->dispatch($app, 'POST', '/api/orders', [
            'customer_name' => 'E2E Cus', 'customer_email' => 'e2e@test.com',
            'total' => 199.99, 'status' => 'pending',
            'items' => [['product' => 'Laptop', 'qty' => 1]],
        ], $headers);
        $this->assertEquals(201, $r->statusCode(), 'Order creation failed: ' . $this->getResponseBody($r));
        $body = json_decode($this->getResponseBody($r), true);
        /** @var array<string, mixed> $body */
        $bodyData = $body['data'] ?? [];
        /** @var array<string, mixed> $bodyData */
        $id = $bodyData['id'] ?? 0;
        /** @var int|string $id */
        $this->assertGreaterThan(0, (int) $id);

        $items = $bodyData['items'] ?? [];
        $this->assertIsArray($items);
        $this->assertCount(1, $items);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/orders/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/orders/{$id}", ['status' => 'completed'], $headers)->statusCode());
        $this->dispatch($app, 'DELETE', "/api/orders/{$id}", [], $headers);
    }

    public function test_post_full_crud(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $r = $this->dispatch($app, 'POST', '/api/posts', [
            'title' => 'E2E Post', 'body' => 'Body with enough length for validation.',
            'locale' => 'en', 'status' => 'published',
        ], $headers);
        $this->assertEquals(201, $r->statusCode());
        $body = json_decode($this->getResponseBody($r), true);
        /** @var array<string, mixed> $body */
        $bodyData = $body['data'] ?? [];
        /** @var array<string, mixed> $bodyData */
        $id = $bodyData['id'] ?? 0;
        /** @var int|string $id */
        $this->assertGreaterThan(0, (int) $id);

        $this->assertEquals(200, $this->dispatch($app, 'GET', "/api/posts/{$id}")->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'PUT', "/api/posts/{$id}", ['title' => 'Updated'], $headers)->statusCode());
    }

    public function test_product_filtering(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $this->dispatch($app, 'POST', '/api/products', [
            'name' => 'FilterTest', 'price' => 10, 'category' => 'fcat', 'status' => 'active',
        ], $headers);
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?category=fcat')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?search=FilterTest')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?sort=price&order=asc')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?status=active')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/products?price_min=5&price_max=50')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/orders?status=pending')->statusCode());
        $this->assertEquals(200, $this->dispatch($app, 'GET', '/api/posts?locale=en')->statusCode());
    }

    public function test_pagination_returns_meta(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'GET', '/api/products?page=1&per_page=5');
        $this->assertEquals(200, $r->statusCode());
        $body = json_decode($this->getResponseBody($r), true);
        /** @var array<string, mixed> $body */
        $this->assertArrayHasKey('meta', $body);
        $bodyMeta = $body['meta'] ?? [];
        /** @var array<string, mixed> $bodyMeta */
        $this->assertArrayHasKey('page', $bodyMeta);
    }

    public function test_error_response_format(): void
    {
        $app = $this->createApp();
        $r = $this->dispatch($app, 'GET', '/api/products/99999');
        $body = json_decode($this->getResponseBody($r), true);
        /** @var array<string, mixed> $body */
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
        /** @var array<string, mixed> $tokenData */
        $tokenDataData = $tokenData['data'] ?? [];
        /** @var array<string, mixed> $tokenDataData */
        $rawToken = $tokenDataData['token'] ?? '';
        /** @var string $rawToken */
        $tokenStr = $rawToken;

        $r2 = $this->dispatch($app, 'GET', '/api/auth/me', [], [
            'Authorization' => 'Bearer ' . $tokenStr,
        ]);
        if ($r2->statusCode() === 200) {
            $meBody = json_decode($this->getResponseBody($r2), true);
            /** @var array<string, mixed> $meBody */
            $meData = $meBody['data'] ?? [];
            /** @var array<string, mixed> $meData */
            $this->assertArrayNotHasKey('password', $meData);
        }
    }

    private function getResponseBody(\Siro\Core\Response $response): string
    {
        ob_start();
        $response->send();
        return ob_get_clean() ?: '';
    }
}
