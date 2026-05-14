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
        /** @var Response $response */
        $this->assertEquals(401, $response->statusCode());
    }

    public function testAuthMiddlewareBlocksInvalidToken(): void
    {
        $request = new Request('GET', '/api/protected', [], ['authorization' => 'Bearer invalid-token']);
        $next = fn (Request $req): Response => Response::success();
        $response = $this->middleware->handle($request, $next);
        /** @var Response $response */
        $this->assertEquals(401, $response->statusCode());
    }

    public function testAuthMiddlewareBlocksEmptyBearer(): void
    {
        $request = new Request('GET', '/api/protected', [], ['authorization' => 'Bearer ']);
        $next = fn (Request $req): Response => Response::success();
        $response = $this->middleware->handle($request, $next);
        /** @var Response $response */
        $this->assertEquals(401, $response->statusCode());
    }

    public function testAuthMiddlewareFileExists(): void
    {
        $this->assertFileExists($this->basePath . '/app/Middleware/AuthMiddleware.php');
    }

    public function testThrottleMiddlewareFileExists(): void
    {
        $reflection = new \ReflectionClass(\Siro\Core\Middleware\ThrottleMiddleware::class);
        $this->assertFileExists((string) $reflection->getFileName());
    }

    public function testCorsMiddlewareFileExists(): void
    {
        $reflection = new \ReflectionClass(\Siro\Core\Middleware\CorsMiddleware::class);
        $this->assertFileExists((string) $reflection->getFileName());
    }

    public function testThrottleMiddlewareClassExists(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Middleware\ThrottleMiddleware::class));
    }

    public function testCorsConfiguredInEnv(): void
    {
        $envPath = $this->basePath . '/.env';

        if (!file_exists($envPath)) {
            $this->markTestSkipped('.env file not found');
        }

        $env = file_get_contents($envPath);
        $this->assertStringContainsString('CORS_ALLOWED_ORIGINS', $env ?: '');
    }
}
