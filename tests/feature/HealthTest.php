<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class HealthTest extends TestCase
{
    public function testHealthReturns200(): void
    {
        $res = $this->get('/health');
        $res->assertOk();
    }

    public function testHealthReturnsHealthyStatus(): void
    {
        $res = $this->get('/health');
        $body = $res->json();
        $this->assertTrue($body['success'] ?? false);
        $this->assertEquals('healthy', $body['data']['status'] ?? '');
    }

    public function testHealthIncludesVersion(): void
    {
        $res = $this->get('/health');
        $body = $res->json();
        $this->assertNotEmpty($body['data']['version'] ?? '');
    }

    public function testHealthIncludesPhpVersion(): void
    {
        $res = $this->get('/health');
        $body = $res->json();
        $this->assertNotEmpty($body['data']['php'] ?? '');
    }

    public function testHealthShowsDatabaseConnected(): void
    {
        $res = $this->get('/health');
        $body = $res->json();
        $this->assertEquals('connected', $body['data']['database'] ?? '');
    }

    public function testHealthReadyReturns200(): void
    {
        $res = $this->get('/health/ready');
        $res->assertOk();
    }
}
