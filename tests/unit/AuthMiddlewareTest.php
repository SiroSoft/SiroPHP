<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Middleware\AuthMiddleware;
use App\Tests\TestCase;
use Siro\Core\Request;
use Siro\Core\Response;

final class AuthMiddlewareTest extends TestCase
{
    private AuthMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AuthMiddleware();
    }

    public function testBlocksMissingToken(): void
    {
        $request = new Request('GET', '/api/protected');
        $next = fn (Request $req): Response => Response::success();
        $response = $this->middleware->handle($request, $next);
        $this->assertEquals(401, $response->statusCode());
    }

    public function testBlocksInvalidToken(): void
    {
        $request = new Request('GET', '/api/protected', [], ['authorization' => 'Bearer invalid-token']);
        $next = fn (Request $req): Response => Response::success();
        $response = $this->middleware->handle($request, $next);
        $this->assertEquals(401, $response->statusCode());
    }

    public function testBlocksEmptyBearer(): void
    {
        $request = new Request('GET', '/api/protected', [], ['authorization' => 'Bearer ']);
        $next = fn (Request $req): Response => Response::success();
        $response = $this->middleware->handle($request, $next);
        $this->assertEquals(401, $response->statusCode());
    }
}
