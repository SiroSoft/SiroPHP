<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class HealthTest extends TestCase
{
    public function testHealthReturns200(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $this->assertEquals(200, $response->statusCode());
    }

    public function testHealthReturnsHealthyStatus(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $this->assertTrue($payload['success'] ?? false);
        $this->assertEquals('healthy', $payload['data']['status'] ?? '');
    }

    public function testHealthShowsDatabaseConnected(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $this->assertEquals('connected', $payload['data']['database'] ?? '');
    }

    public function testHealthIncludesVersion(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $this->assertNotEmpty($payload['data']['version'] ?? '');
    }

    public function testHealthIncludesPhpVersion(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $this->assertNotEmpty($payload['data']['php'] ?? '');
    }

    public function testHealthIncludesTimestamp(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $this->assertNotEmpty($payload['data']['time'] ?? '');
    }
}
