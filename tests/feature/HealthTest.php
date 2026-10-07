<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class HealthTest extends TestCase
{
    public function testHealthReturns200(): void
    {
        $res = $this->get('/health', $this->authenticate());
        $res->assertOk();
    }

    public function testHealthReturnsHealthyStatus(): void
    {
        $res = $this->get('/health', $this->authenticate());
        $body = $res->json();
        $this->assertTrue($body['success'] ?? false);
        $this->assertEquals('healthy', $body['data']['status'] ?? '');
    }

    public function testHealthIncludesDatabase(): void
    {
        $res = $this->get('/health', $this->authenticate());
        $body = $res->json();
        $this->assertNotEmpty($body['data']['database'] ?? '');
    }

    public function testHealthShowsDatabaseConnected(): void
    {
        $res = $this->get('/health', $this->authenticate());
        $body = $res->json();
        $this->assertEquals('connected', $body['data']['database'] ?? '');
    }

    public function testHealthReadyReturns200(): void
    {
        $res = $this->get('/health/ready', $this->authenticate());
        $res->assertOk();
    }

    public function testLivenessRemainsPublicAndDoesNotExposeDependencies(): void
    {
        $body = $this->get('/health/live')->json();
        $this->assertTrue($body['success'] ?? false);
        $this->assertSame('alive', $body['data']['status'] ?? null);
        $this->assertArrayNotHasKey('database', $body['data'] ?? []);
    }

    public function testReadinessIsNotPublic(): void
    {
        $this->get('/health/ready')->assertUnauthorized();
    }

    public function testDetailedHealthIsNotPublic(): void
    {
        $this->get('/health')->assertUnauthorized();
    }
}
