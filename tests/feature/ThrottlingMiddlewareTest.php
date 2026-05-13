<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class ThrottlingMiddlewareTest extends TestCase
{
    public function testThrottleMiddlewareFileExists(): void { $this->assertTrue(class_exists(\Siro\Core\Middleware\ThrottleMiddleware::class)); }
    public function testEnvHasThrottleConfig(): void
    {
        $envPath = $this->basePath . '/.env';

        // Skip if .env file doesn't exist (e.g., in some CI environments)
        if (!file_exists($envPath)) {
            $this->markTestSkipped('.env file not found');
        }

        $env = file_get_contents($envPath);
        $this->assertStringContainsString('THROTTLE', $env ?: '');
    }
}
