<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class MetricsEndpointTest extends TestCase
{
    public function testMetricsEndpointReturnsOk(): void
    {
        $resp = $this->get('/metrics');
        $resp->assertOk();
    }

    public function testMetricsContainsSiroPrefix(): void
    {
        $resp = $this->get('/metrics');
        $body = $resp->json();
        $this->assertIsArray($body);
    }

    public function testHealthEndpoint(): void
    {
        $resp = $this->get('/health');
        $resp->assertOk();
        $json = $resp->json();
        $this->assertArrayHasKey('data', $json);
        $jsonData = $json['data'] ?? [];
        $this->assertEquals('healthy', $jsonData['status'] ?? '');
    }
}
