<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class ProductTest extends TestCase
{
    private array $adminHeaders = [];

    protected function setUp(): void
    {
        parent::setUp();
        $app = $this->createApp();
        $this->adminHeaders = $this->authenticate($app);
    }

    public function testIndexReturns200(): void
    {
        $this->get('/api/products', $this->adminHeaders)->assertOk();
    }

    public function testShowReturns404ForInvalidId(): void
    {
        $this->get('/api/products/999', $this->adminHeaders)->assertNotFound();
    }

    public function testStoreReturns201WithValidData(): void
    {
        $this->post('/api/products', ['name' => 'Test Product', 'price' => 10, 'stock' => 5], $this->adminHeaders)->assertCreated();
    }

    public function testStoreReturns422WithoutRequiredFields(): void
    {
        $this->post('/api/products', [], $this->adminHeaders)->assertValidationError();
    }
}
