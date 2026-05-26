<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class FromTracedemo_9f196c1e66e4fdbfd6ffa17f8092Test extends TestCase
{
    public function test_post_api_orders(): void
    {
        $headers = $this->authenticate();
        $response = $this->post('/api/orders', array (
          'product_id' => 10,
          'quantity' => 2,
          'shipping_address' => 
          array (
            'street' => '123 Main St',
            'city' => 'Ho Chi Minh',
            'country' => 'VN',
          ),
        ), $headers);
        $response->assertStatus(422);

        $body = $response->json();
        $this->assertArrayHasKey('success', $body);
        $this->assertArrayHasKey('message', $body);
        
        $response->assertJsonPath('success', false);
    }
}
