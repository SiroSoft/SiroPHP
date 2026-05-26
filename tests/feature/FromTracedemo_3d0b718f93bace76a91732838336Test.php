<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class FromTracedemo_3d0b718f93bace76a91732838336Test extends TestCase
{
    public function test_post_api_orders(): void
    {
        $headers = $this->authenticate();
        $response = $this->post('/api/orders', array (
          'product_id' => 10,
          'quantity' => 2,
        ), $headers);
        $response->assertStatus(422);

        $body = $response->json();
        $this->assertArrayHasKey('success', $body);
        $this->assertArrayHasKey('message', $body);
        
        $response->assertJsonPath('success', false);
    }
}
