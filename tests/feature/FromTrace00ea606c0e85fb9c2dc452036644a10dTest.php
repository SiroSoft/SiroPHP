<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class FromTrace00ea606c0e85fb9c2dc452036644a10dTest extends TestCase
{
    public function test_post_api_auth_register(): void
    {
        $response = $this->post('/api/auth/register', array (
          'name' => 'Admin',
          'email' => 'admin@shop.com',
          'password' => '[REDACTED]',
          'password_confirmation' => '[REDACTED]',
        ));
        $response->assertCreated();

        // Verify JSON structure
        $body = $response->json();
        $this->assertArrayHasKey('success', $body);
        $this->assertArrayHasKey('message', $body);
        
        $response->assertJsonPath('success', true);
    }
}
