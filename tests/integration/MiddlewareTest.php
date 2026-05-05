<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use Siro\Core\Request;
use Siro\Core\Response;
use App\Middleware\AuthMiddleware;

final class MiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthMiddleware();
    }

    public function testAuthMiddlewareBlocksMissingToken(): void
    {
        $request = new Request('GET', '/api/protected');
        $next = fn (Request $req): Response => Response::success();
        $response = $this->middleware->handle($request, $next);
        $this->assertEquals(401, $response->statusCode());
    }

    public function testAuthMiddlewareBlocksInvalidToken(): void
    {
        $request = new Request('GET', '/api/protected', [], ['authorization' => 'Bearer invalid-token']);
        $next = fn (Request $req): Response => Response::success();
        $response = $this->middleware->handle($request, $next);
        $this->assertEquals(401, $response->statusCode());
    }

    public function testAuthMiddlewareBlocksEmptyBearer(): void
    {
        $request = new Request('GET', '/api/protected', [], ['authorization' => 'Bearer ']);
        $next = fn (Request $req): Response => Response::success();
        $response = $this->middleware->handle($request, $next);
        $this->assertEquals(401, $response->statusCode());
    }

    public function testAuthMiddlewareFileExists(): void
    {
        $this->assertFileExists($this->basePath . '/app/Middleware/AuthMiddleware.php');
    }

    public function testThrottleMiddlewareFileExists(): void
    {
        $this->assertFileExists($this->basePath . '/app/Middleware/ThrottleMiddleware.php');
    }

    public function testCorsMiddlewareFileExists(): void
    {
        $this->assertFileExists($this->basePath . '/app/Middleware/CorsMiddleware.php');
    }

    public function testThrottleRateLimitDirectoryExists(): void
    {
        $rateLimitDir = $this->basePath . '/storage/rate_limit';
        $this->assertDirectoryExists($rateLimitDir);
    }

    public function testCorsConfiguredInEnv(): void
    {
        $env = file_get_contents($this->basePath . '/.env');
        $this->assertStringContainsString('CORS_ALLOWED_ORIGINS', $env);
    }
}
