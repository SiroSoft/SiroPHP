<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class OrderTestTest extends TestCase
{
    public function testIndexReturns200(): void
    {
        $this->get('/api/order')->assertOk();
    }

    public function testShowReturns404ForInvalidId(): void
    {
        $this->get('/api/order/999')->assertNotFound();
    }

    public function testStoreReturns201WithValidData(): void
    {
        $this->post('/api/order', ['name' => 'Test'])->assertCreated();
    }

    public function testStoreReturns422WithoutRequiredFields(): void
    {
        $this->post('/api/order', [])->assertValidationError();
    }
}
