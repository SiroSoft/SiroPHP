<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Middleware\DemoGuardMiddleware;
use App\Middleware\FeGuardMiddleware;
use App\Support\Turnstile;
use App\Support\Uploader;
use App\Tests\TestCase;
use Siro\Core\Request;
use Siro\Core\Response;

/**
 * REQ-2/REQ-3/REQ-9: Turnstile fail-open/fail-closed, FeGuard FE token,
 * DemoGuard read-only demo, Uploader error branches. (Demo branch: Guards
 * exist here; release branch keeps the Turnstile/Uploader half.)
 */
final class SecurityGuardsTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $savedEnv = [];

    private const MANAGED_KEYS = ['TURNSTILE_SECRET', 'FE_SHARED_SECRET', 'DEMO_EMAIL', 'UPLOAD_MAX_MB'];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::MANAGED_KEYS as $key) {
            $this->savedEnv[$key] = getenv($key);
            unset($_ENV[$key]);
            putenv($key);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::MANAGED_KEYS as $key) {
            $saved = $this->savedEnv[$key] ?? false;
            if ($saved === false) {
                unset($_ENV[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = $saved;
                putenv($key . '=' . $saved);
            }
        }
        parent::tearDown();
    }

    // ── Turnstile ──────────────────────────────────────────────

    public function testTurnstileFailOpenWithoutSecret(): void
    {
        $this->assertFalse(Turnstile::isConfigured());
        $this->assertTrue(Turnstile::verify('anything'));
        $this->assertTrue(Turnstile::verify(''));
    }

    public function testTurnstileRejectsEmptyTokenWhenConfigured(): void
    {
        $_ENV['TURNSTILE_SECRET'] = 'test-secret';
        $this->assertTrue(Turnstile::isConfigured());
        // Empty token is rejected locally without any network call.
        $this->assertFalse(Turnstile::verify(''));
        $this->assertFalse(Turnstile::verify('   '));
    }

    // ── FeGuard ────────────────────────────────────────────────

    public function testFeGuardPassesOptionsWithoutSecret(): void
    {
        $guard = new FeGuardMiddleware();
        $request = new Request('OPTIONS', '/api/products');
        $called = false;
        $response = $guard->handle($request, function () use (&$called): Response {
            $called = true;
            return Response::success();
        });
        $this->assertTrue($called);
        $this->assertSame(200, $response->statusCode());
    }

    public function testFeGuardFailOpenWithoutSecret(): void
    {
        $guard = new FeGuardMiddleware();
        $request = new Request('GET', '/api/products');
        $response = $guard->handle($request, fn (): Response => Response::success());
        $this->assertSame(200, $response->statusCode());
    }

    public function testFeGuardBlocksMissingToken(): void
    {
        $_ENV['FE_SHARED_SECRET'] = 's3cret';
        $guard = new FeGuardMiddleware();
        $request = new Request('GET', '/api/products');
        $response = $guard->handle($request, fn (): Response => Response::success());
        $this->assertSame(403, $response->statusCode());
    }

    public function testFeGuardBlocksWrongToken(): void
    {
        $_ENV['FE_SHARED_SECRET'] = 's3cret';
        $guard = new FeGuardMiddleware();
        $request = new Request('GET', '/api/products', [], ['x-siro-fe' => 'wrong']);
        $response = $guard->handle($request, fn (): Response => Response::success());
        $this->assertSame(403, $response->statusCode());
    }

    public function testFeGuardAllowsMatchingToken(): void
    {
        $_ENV['FE_SHARED_SECRET'] = 's3cret';
        $guard = new FeGuardMiddleware();
        $request = new Request('POST', '/api/products', [], ['x-siro-fe' => 's3cret']);
        $called = false;
        $response = $guard->handle($request, function () use (&$called): Response {
            $called = true;
            return Response::success();
        });
        $this->assertTrue($called);
        $this->assertSame(200, $response->statusCode());
    }

    // ── DemoGuard ──────────────────────────────────────────────

    public function testDemoGuardAllowsReadsForViewer(): void
    {
        $guard = new DemoGuardMiddleware();
        $request = new Request('GET', '/api/products');
        $request->setUser(['id' => 3, 'email' => 'demo@skeleton.sirophp.com', 'role' => 'viewer']);
        $response = $guard->handle($request, fn (): Response => Response::success());
        $this->assertSame(200, $response->statusCode());
    }

    public function testDemoGuardAllowsWritesForAdmin(): void
    {
        $guard = new DemoGuardMiddleware();
        $request = new Request('POST', '/api/products');
        $request->setUser(['id' => 1, 'email' => 'admin@skeleton.sirophp.com', 'role' => 'admin']);
        $called = false;
        $response = $guard->handle($request, function () use (&$called): Response {
            $called = true;
            return Response::success();
        });
        $this->assertTrue($called);
        $this->assertSame(200, $response->statusCode());
    }

    public function testDemoGuardBlocksWritesForViewerRole(): void
    {
        $guard = new DemoGuardMiddleware();
        $request = new Request('DELETE', '/api/products/1');
        $request->setUser(['id' => 9, 'email' => 'viewer@example.com', 'role' => 'viewer']);
        $response = $guard->handle($request, fn (): Response => Response::success());
        $this->assertSame(403, $response->statusCode());
    }

    public function testDemoGuardBlocksWritesForDemoEmail(): void
    {
        $_ENV['DEMO_EMAIL'] = 'demo@skeleton.sirophp.com';
        $guard = new DemoGuardMiddleware();
        // Even with admin role, the demo email stays read-only.
        $request = new Request('PUT', '/api/products/1');
        $request->setUser(['id' => 3, 'email' => 'demo@skeleton.sirophp.com', 'role' => 'admin']);
        $response = $guard->handle($request, fn (): Response => Response::success());
        $this->assertSame(403, $response->statusCode());
    }

    // ── Uploader error branches ────────────────────────────────

    public function testUploaderRejectsMissingFile(): void
    {
        $request = new Request('POST', '/api/upload');
        $result = Uploader::handle($request, 'file', 'test');
        $this->assertTrue($result['error']);
        $this->assertSame(422, $result['response']->statusCode());
    }

    public function testUploaderResponseReturns422WithoutFile(): void
    {
        $request = new Request('POST', '/api/upload');
        $response = Uploader::response($request, 'file', 'test');
        $this->assertSame(422, $response->statusCode());
    }

    public function testUploaderMaxBytesRespectsEnv(): void
    {
        $_ENV['UPLOAD_MAX_MB'] = '5';
        $method = new \ReflectionMethod(Uploader::class, 'maxBytes');
        $method->setAccessible(true);
        $this->assertSame(5 * 1024 * 1024, $method->invoke(null));
    }
}
