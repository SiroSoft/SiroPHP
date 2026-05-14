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
        $data = $payload['data'] ?? [];
        /** @var array<string, mixed> $data */
        $this->assertTrue($payload['success'] ?? false);
        $this->assertEquals('healthy', $data['status'] ?? '');
    }

    public function testHealthShowsDatabaseConnected(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $data = $payload['data'] ?? [];
        /** @var array<string, mixed> $data */
        $this->assertEquals('connected', $data['database'] ?? '');
    }

    public function testHealthIncludesVersion(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $data = $payload['data'] ?? [];
        /** @var array<string, mixed> $data */
        $this->assertNotEmpty($data['version'] ?? '');
    }

    public function testHealthIncludesPhpVersion(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $data = $payload['data'] ?? [];
        /** @var array<string, mixed> $data */
        $this->assertNotEmpty($data['php'] ?? '');
    }

    public function testHealthIncludesTimestamp(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/health');
        $payload = $response->payload();
        $data = $payload['data'] ?? [];
        /** @var array<string, mixed> $data */
        $this->assertNotEmpty($data['time'] ?? '');
    }
}
