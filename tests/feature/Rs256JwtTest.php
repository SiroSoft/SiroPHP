<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Auth\JWT;
use Siro\Core\Env;

final class Rs256JwtTest extends TestCase
{
    public function testJwtAlgorithmEnvCanBeSet(): void { putenv("JWT_ALGORITHM=RS256"); $this->assertSame('RS256', Env::get('JWT_ALGORITHM', 'HS256')); }
    public function testJwtClassHasEncodeRefreshMethod(): void { $this->assertTrue(method_exists(JWT::class, 'encodeRefresh')); }
    public function testJwtClassHasDecodeMethod(): void { $this->assertTrue(method_exists(JWT::class, 'decode')); }
    public function testOpenSslExtensionIsLoaded(): void { $this->assertTrue(extension_loaded('openssl')); }
    public function testPrivateKeyExportSupported(): void { $this->assertTrue(function_exists('openssl_pkey_export')); }
    public function testPublicKeyExtractionSupported(): void { $this->assertTrue(function_exists('openssl_pkey_get_details')); }
}
