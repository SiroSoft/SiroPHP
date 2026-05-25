<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class ProductTest extends TestCase
{
    public function testIndexReturns200(): void
    {
        $this->get('/api/Product')->assertOk();
    }

    public function testShowReturns404ForInvalidId(): void
    {
        $this->get('/api/Product/999')->assertNotFound();
    }

    public function testStoreReturns201WithValidData(): void
    {
        $this->post('/api/Product', ['name' => 'Test Product'])->assertCreated();
    }

    public function testStoreReturns422WithoutRequiredFields(): void
    {
        $this->post('/api/Product', [])->assertValidationError();
    }
}
