<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Request;
use Siro\Core\Response;
use App\Middleware\SecurityHeadersMiddleware;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new SecurityHeadersMiddleware();
    }

    public function testXFrameOptionsHeaderSet(): void
    {
        $request = new Request('GET', '/test');
        $response = $this->middleware->handle($request, fn () => Response::success());
        $this->assertStringContainsString('X-Frame-Options: DENY', implode("\n", $response->getHeaders()));
    }

    public function testXContentTypeOptionsHeaderSet(): void
    {
        $request = new Request('GET', '/test');
        $response = $this->middleware->handle($request, fn () => Response::success());
        $this->assertStringContainsString('X-Content-Type-Options: nosniff', implode("\n", $response->getHeaders()));
    }

    public function testReferrerPolicyHeaderSet(): void
    {
        $request = new Request('GET', '/test');
        $response = $this->middleware->handle($request, fn () => Response::success());
        $this->assertStringContainsString('Referrer-Policy: strict-origin-when-cross-origin', implode("\n", $response->getHeaders()));
    }

    public function testPermissionsPolicyHeaderSet(): void
    {
        $request = new Request('GET', '/test');
        $response = $this->middleware->handle($request, fn () => Response::success());
        $this->assertStringContainsString('Permissions-Policy', implode("\n", $response->getHeaders()));
    }

    public function testContentSecurityPolicyHeaderSet(): void
    {
        $request = new Request('GET', '/test');
        $response = $this->middleware->handle($request, fn () => Response::success());
        $this->assertStringContainsString('Content-Security-Policy', implode("\n", $response->getHeaders()));
    }

    public function testAllRequiredHeadersPresent(): void
    {
        $request = new Request('GET', '/test');
        $response = $this->middleware->handle($request, fn () => Response::success());
        $headers = implode("\n", $response->getHeaders());

        $requiredHeaders = [
            'X-Frame-Options',
            'X-Content-Type-Options',
            'Referrer-Policy',
            'Permissions-Policy',
            'Content-Security-Policy',
        ];

        foreach ($requiredHeaders as $header) {
            $this->assertStringContainsString($header, $headers, "Missing security header: {$header}");
        }
    }
}
