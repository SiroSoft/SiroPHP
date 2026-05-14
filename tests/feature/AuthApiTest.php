<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class AuthApiTest extends TestCase
{
    public function testHealthEndpoint(): void
    {
        $this->get('/health')->assertOk()->assertJson(['success' => true]);
    }

    public function testRootEndpoint(): void
    {
        $this->get('/')->assertOk();
    }

    public function testRegisterEndpoint(): void
    {
        $resp = $this->post('/api/auth/register', [
            'name' => 'Test',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'secret123',
        ]);
        $this->assertContains($resp->status(), [200, 201, 422]);
    }

    public function testLoginWithEmptyDataReturnsValidation(): void
    {
        $resp = $this->post('/api/auth/login', []);
        $this->assertEquals(422, $resp->status());
    }

    public function testLoginReturns422ForInvalidEmail(): void
    {
        $resp = $this->post('/api/auth/login', ['email' => 'invalid', 'password' => '123456']);
        $this->assertEquals(422, $resp->status());
    }

    public function testProductsEndpoint(): void
    {
        $this->get('/api/products')->assertOk();
    }

    public function testProductsShowReturns404(): void
    {
        $resp = $this->get('/api/products/99999');
        $this->assertEquals(404, $resp->status());
    }

    public function testCategoriesEndpoint(): void
    {
        $this->get('/api/categories')->assertOk();
    }

    public function testUsersEndpoint(): void
    {
        $this->get('/api/users')->assertOk();
    }

    public function testUsersShowReturns404(): void
    {
        $resp = $this->get('/api/users/99999');
        $this->assertEquals(404, $resp->status());
    }

    public function testTagsEndpoint(): void
    {
        $this->get('/api/tags')->assertOk();
    }

    public function testOrdersEndpoint(): void
    {
        $this->get('/api/orders')->assertOk();
    }

    public function testPostsEndpoint(): void
    {
        $this->get('/api/posts')->assertOk();
    }

    public function testCreateProduct(): void
    {
        $resp = $this->post('/api/products', ['name' => 'Test']);
        $this->assertContains($resp->status(), [200, 201, 401, 422]);
    }

    public function testUpdateProduct(): void
    {
        $resp = $this->put('/api/products/1', ['name' => 'Updated']);
        $this->assertContains($resp->status(), [200, 401, 404, 422]);
    }

    public function testDeleteProduct(): void
    {
        $resp = $this->delete('/api/products/1');
        $this->assertContains($resp->status(), [200, 204, 401, 404]);
    }

    public function testHealthResponseVersion(): void
    {
        $resp = $this->get('/health');
        $json = $resp->json();
        $jsonData = $json['data'] ?? [];
        /** @var array<string, mixed> $jsonData */
        $rawVersion = $jsonData['version'] ?? '';
        /** @var string $rawVersion */
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $rawVersion);
    }
}
