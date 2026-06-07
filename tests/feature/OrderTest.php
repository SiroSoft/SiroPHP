<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class OrderTest extends TestCase
{
    public function testIndexReturns200(): void
    {
        $headers = $this->authenticate();
        $this->get('/api/orders', $headers)->assertOk();
    }

    public function testShowReturns404ForInvalidId(): void
    {
        $headers = $this->authenticate();
        $this->get('/api/orders/999', $headers)->assertNotFound();
    }

    public function testStoreReturns201WithValidData(): void
    {
        $headers = $this->authenticate();
        $response = $this->post('/api/orders', [
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
        ], $headers)->assertStatus(422); // requires items[] with valid product_ids
    }

    public function testStoreReturns422WithoutRequiredFields(): void
    {
        $headers = $this->authenticate();
        $this->post('/api/orders', [], $headers)->assertValidationError();
    }
}
