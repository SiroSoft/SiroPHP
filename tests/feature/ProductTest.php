<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class ProductTest extends TestCase
{
    private array $authHeaders = [];

    protected function setUp(): void
    {
        parent::setUp();
        $app = $this->createApp();
        $this->authHeaders = $this->authenticate($app);
    }

    public function testListProducts(): void
    {
        $res = $this->get('/api/Product', $this->authHeaders);
        $res->assertOk();
        $body = $res->json();
        $this->assertArrayHasKey('data', $body);
    }

    public function testCreateProduct(): void
    {
        $res = $this->post('/api/Product', [
            'name' => 'Test Laptop',
            'sku' => 'TST-LAP-001',
            'price' => '1500.00',
            'stock' => '100',
            'category' => 'Electronics',
            'brand' => 'TestBrand',
            'status' => 'active',
        ], $this->authHeaders);
        $this->assertContains($res->status(), [200, 201]);
    }

    public function testCreateProductFailsWithoutName(): void
    {
        $res = $this->post('/api/Product', [
            'price' => '100',
        ], $this->authHeaders);
        $res->assertStatus(422);
    }

    public function testCreateProductFailsWithoutAuth(): void
    {
        $res = $this->post('/api/Product', ['name' => 'Test']);
        $res->assertStatus(401);
    }

    public function testShowProduct(): void
    {
        $res = $this->get('/api/Product/1', $this->authHeaders);
        $this->assertContains($res->status(), [200, 404]);
    }

    public function testUpdateProduct(): void
    {
        $res = $this->put('/api/Product/1', [
            'name' => 'Updated Product',
            'price' => '99.99',
        ], $this->authHeaders);
        $this->assertContains($res->status(), [200, 404]);
    }

    public function testDeleteProduct(): void
    {
        $res = $this->delete('/api/Product/999999', $this->authHeaders);
        $this->assertContains($res->status(), [200, 404]);
    }

    public function testPagination(): void
    {
        $res = $this->get('/api/Product?page=1&per_page=10', $this->authHeaders);
        $res->assertOk();
        $body = $res->json();
        $this->assertArrayHasKey('meta', $body);
        $this->assertArrayHasKey('page', $body['meta']);
    }
}
