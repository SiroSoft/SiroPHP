<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Env;
use Siro\Core\Request;
use Siro\Core\Response;
use App\Middleware\CorsMiddleware;

final class CorsMiddlewareTest extends TestCase
{
    private CorsMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CorsMiddleware();
        $_ENV['CORS_ALLOWED_ORIGINS'] = 'http://localhost:8080';
        $_ENV['CORS_ALLOWED_METHODS'] = 'GET,POST,PUT,DELETE,OPTIONS';
        $_ENV['CORS_ALLOWED_HEADERS'] = 'Content-Type,Authorization';
        putenv('CORS_ALLOWED_ORIGINS=http://localhost:8080');
        putenv('CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS');
        putenv('CORS_ALLOWED_HEADERS=Content-Type,Authorization');
    }

    public function testOptionsRequestReturns204(): void
    {
        $request = new Request('OPTIONS', '/test', [], ['origin' => 'http://localhost:8080']);
        $response = $this->middleware->handle($request, fn () => Response::success());
        $this->assertSame(204, $response->statusCode());
    }

    public function testCorsHeadersOnGetRequest(): void
    {
        $request = new Request('GET', '/test', [], ['origin' => 'http://localhost:8080']);
        $response = $this->middleware->handle($request, fn () => Response::success());
        $headers = implode("\n", $response->getHeaders());

        $this->assertStringContainsString('Access-Control-Allow-Origin', $headers);
        $this->assertStringContainsString('Access-Control-Allow-Methods', $headers);
        $this->assertStringContainsString('Access-Control-Allow-Headers', $headers);
    }

    public function testCorsAllowCredentialsIsTrueWhenOriginIsSpecific(): void
    {
        $request = new Request('GET', '/test', [], ['origin' => 'http://localhost:8080']);
        $response = $this->middleware->handle($request, fn () => Response::success());
        $headers = implode("\n", $response->getHeaders());

        $this->assertStringContainsString('Access-Control-Allow-Credentials: true', $headers);
    }

    public function testVaryHeaderIsSet(): void
    {
        $request = new Request('GET', '/test', [], ['origin' => 'http://localhost:8080']);
        $response = $this->middleware->handle($request, fn () => Response::success());
        $headers = implode("\n", $response->getHeaders());

        $this->assertStringContainsString('Vary: Origin', $headers);
    }

    public function testUnknownOriginRejected(): void
    {
        $request = new Request('GET', '/test', [], ['origin' => 'http://evil.com']);
        $response = $this->middleware->handle($request, fn () => Response::success());
        $headers = implode("\n", $response->getHeaders());

        $this->assertStringContainsString('Access-Control-Allow-Origin: http://localhost:8080', $headers);
    }

    public function testWildcardOriginReturnsRequestOrigin(): void
    {
        $_ENV['CORS_ALLOWED_ORIGINS'] = '*';
        putenv('CORS_ALLOWED_ORIGINS=*');

        $middleware = new CorsMiddleware();
        $request = new Request('GET', '/test', [], ['origin' => 'http://example.com']);
        $response = $middleware->handle($request, fn () => Response::success());
        $headers = implode("\n", $response->getHeaders());

        $this->assertStringContainsString('Access-Control-Allow-Origin: http://example.com', $headers);
    }
}
