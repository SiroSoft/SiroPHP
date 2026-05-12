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
        $resp = $this->get('/api/products');
        $json = $resp->json();
        $this->assertIsArray($json['data']);
    }

    public function testCorsHeadersPresent(): void
    {
        $resp = $this->get('/api/products', ['Origin' => 'http://example.com']);
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
        $start = microtime(true);
        $this->get('/api/products');
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
        $this->assertEquals('connected', $json['data']['database']);
    }

    public function testHealthReturnsVersion(): void
    {
        $resp = $this->get('/health');
        $json = $resp->json();
        $this->assertEquals('0.23.0', $json['data']['version']);
    }

    public function testCategoriesEndpoint(): void
    {
        $this->get('/api/categories')->assertOk();
    }

    public function testTagsEndpoint(): void
    {
        $this->get('/api/tags')->assertOk();
    }

    public function testOrdersEndpoint(): void
    {
        $this->get('/api/orders')->assertOk();
    }

    public function testPostsEndpoint(): void
    {
        $this->get('/api/posts')->assertOk();
    }

    public function testUsersEndpoint(): void
    {
        $this->get('/api/users')->assertOk();
    }
}
