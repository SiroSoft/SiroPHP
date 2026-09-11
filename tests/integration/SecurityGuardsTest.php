<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Support\Turnstile;
use App\Support\Uploader;
use App\Tests\TestCase;
use Siro\Core\Request;

/**
 * REQ-2/REQ-9: Turnstile fail-open/fail-closed and Uploader error branches.
 *
 * NOTE (branch strategy): FeGuard/DemoGuard live on demo/skeleton-demo only,
 * so their tests live there too (same file + guard sections). This release
 * branch keeps the demo-agnostic half.
 */
final class SecurityGuardsTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $savedEnv = [];

    private const MANAGED_KEYS = ['TURNSTILE_SECRET', 'UPLOAD_MAX_MB'];

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
