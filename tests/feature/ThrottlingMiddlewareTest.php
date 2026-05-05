<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class ThrottlingMiddlewareTest extends TestCase
{
    public function testThrottleMiddlewareFileExists(): void { $this->assertFileExists($this->basePath . '/app/Middleware/ThrottleMiddleware.php'); }
    public function testRateLimitStorageDirectoryExists(): void { $this->assertDirectoryExists($this->basePath . '/storage/rate_limit'); }
    public function testEnvHasThrottleConfig(): void { $env = file_get_contents($this->basePath . '/.env'); $this->assertStringContainsString('THROTTLE', $env); }
}
