<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class FromTrace00ea606c0e85fb9c2dc452036644a10dTest extends TestCase
{
    public function test_post_api_auth_register(): void
    {
        $email = 'admin-' . uniqid() . '@shop.com';
        $response = $this->post('/api/auth/register', array (
          'name' => 'Admin',
          'email' => $email,
          'password' => 'secret123',
          'password_confirmation' => 'secret123',
        ));
        $response->assertCreated();

        $body = $response->json();
        $this->assertArrayHasKey('success', $body);
        $this->assertArrayHasKey('message', $body);

        $response->assertJsonPath('success', true);
    }
}
