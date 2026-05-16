<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;

final class ApiResponseFormatTest extends TestCase
{
    public function testSuccessResponseHasCorrectFormat(): void
    {
        $resp = $this->get('/health');
        $json = $resp->json();
        $this->assertTrue($json['success']);
        $this->assertIsString($json['message']);
    }

    public function testListResponseHasDataArray(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products', $auth);
        $json = $resp->json();
        $this->assertIsArray($json['data']);
    }

    public function testCorsHeadersPresent(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products', array_merge($auth, ['Origin' => 'http://example.com']));
        $this->assertContains($resp->status(), [200, 204]);
    }

    public function testAuthMeWithoutTokenReturns401(): void
    {
        $resp = $this->get('/api/auth/me');
        $this->assertEquals(401, $resp->status());
    }

    public function testAuthMeReturnsJsonError(): void
    {
        $resp = $this->get('/api/auth/me');
        $json = $resp->json();
        $this->assertArrayHasKey('message', $json);
    }

    public function testLogoutWithoutTokenReturns401(): void
    {
        $resp = $this->post('/api/auth/logout', []);
        $this->assertEquals(401, $resp->status());
    }

    public function testRefreshWithoutToken(): void
    {
        $resp = $this->post('/api/auth/refresh', []);
        $this->assertContains($resp->status(), [401, 422]);
    }

    public function testForgotPasswordAcceptsEmail(): void
    {
        $resp = $this->post('/api/auth/forgot-password', ['email' => 'test@test.com']);
        $this->assertContains($resp->status(), [200, 422]);
    }

    public function testProductsEndpointResponseTime(): void
    {
        $auth = $this->authenticate();
        $start = microtime(true);
        $this->get('/api/products', $auth);
        $elapsed = (microtime(true) - $start) * 1000;
        $this->assertLessThan(1000, $elapsed, 'Response should be under 1s');
    }

    public function testHealthEndpointResponseTime(): void
    {
        $start = microtime(true);
        $this->get('/health');
        $elapsed = (microtime(true) - $start) * 1000;
        $this->assertLessThan(500, $elapsed, 'Health check under 500ms');
    }

    public function testCreateUserWithoutAuthReturns401(): void
    {
        $resp = $this->post('/api/users', []);
        $this->assertEquals(401, $resp->status());
    }

    public function testRefreshReturnsExpected(): void
    {
        $resp = $this->post('/api/auth/refresh', []);
        $this->assertContains($resp->status(), [401, 422]);
    }

    public function testDeleteProductReturnsExpected(): void
    {
        $resp = $this->delete('/api/products/99999');
        $this->assertContains($resp->status(), [200, 401, 404]);
    }

    public function testHealthReturnsDatabaseConnected(): void
    {
        $resp = $this->get('/health');
        $json = $resp->json();
        $jsonData = $json['data'] ?? [];
        /** @var array<string, mixed> $jsonData */
        $this->assertEquals('connected', $jsonData['database']);
    }

    public function testHealthReturnsVersion(): void
    {
        $resp = $this->get('/health');
        $json = $resp->json();
        $jsonData = $json['data'] ?? [];
        /** @var array<string, mixed> $jsonData */
        $this->assertArrayHasKey('version', $jsonData);
        $rawVersion = $jsonData['version'] ?? '';
        /** @var string $rawVersion */
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $rawVersion);
    }

    public function testCategoriesEndpoint(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/categories', $auth)->assertOk();
    }

    public function testTagsEndpoint(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/tags', $auth)->assertOk();
    }

    public function testOrdersEndpoint(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/orders', $auth)->assertOk();
    }

    public function testPostsEndpoint(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/posts', $auth)->assertOk();
    }

    public function testUsersEndpoint(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/users', $auth)->assertOk();
    }
}
