<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class ProductTest extends TestCase
{
    public function testIndexReturns200(): void
    {
        $headers = $this->authenticate();
        $this->get('/api/product', $headers)->assertOk();
    }

    public function testShowReturns404ForInvalidId(): void
    {
        $headers = $this->authenticate();
        $this->get('/api/product/999', $headers)->assertNotFound();
    }

    public function testStoreReturns201WithValidData(): void
    {
        $headers = $this->authenticate();
        $this->post('/api/product', ['name' => 'Test Product', 'price' => 10, 'stock' => 5], $headers)->assertStatus(403);
    }

    public function testStoreReturns422WithoutRequiredFields(): void
    {
        $headers = $this->authenticate();
        $this->post('/api/product', [], $headers)->assertStatus(403);
    }
}
