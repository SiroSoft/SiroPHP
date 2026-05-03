<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;

final class ApiTest extends TestCase
{
    public function testRootEndpoint(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/');

        $this->assertEquals(200, $response->statusCode());
        $payload = $response->payload();
        $this->assertArrayHasKey('success', $payload);
        $this->assertTrue($payload['success']);
    }

    public function testNotFound(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/nonexistent');

        $this->assertEquals(404, $response->statusCode());
    }

    public function testAuthMeWithoutTokenReturns401(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/api/auth/me');

        $this->assertEquals(401, $response->statusCode());
    }

    public function testLoginValidation(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'POST', '/api/auth/login', []);

        $this->assertEquals(422, $response->statusCode());
    }

    public function testCacheSetGet(): void
    {
        \Siro\Core\Cache::set('test_key', 'test_value', 60);
        $this->assertEquals('test_value', \Siro\Core\Cache::get('test_key'));
        \Siro\Core\Cache::forget('test_key');
        $this->assertNull(\Siro\Core\Cache::get('test_key'));
    }
}
