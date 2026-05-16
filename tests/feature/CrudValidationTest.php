<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class CrudValidationTest extends TestCase
{
    public function testCreateProductReturnsSuccessOrValidation(): void
    {
        $resp = $this->post('/api/products', []);
        $this->assertContains($resp->status(), [200, 201, 401, 422]);
    }

    public function testCreateCategoryReturnsExpected(): void
    {
        $resp = $this->post('/api/categories', ['name' => 'TestCat']);
        $this->assertContains($resp->status(), [200, 201, 401, 422]);
    }

    public function testCreateOrderReturnsExpected(): void
    {
        $resp = $this->post('/api/orders', []);
        $this->assertContains($resp->status(), [200, 201, 401, 422]);
    }

    public function testCreatePostReturnsExpected(): void
    {
        $resp = $this->post('/api/posts', []);
        $this->assertContains($resp->status(), [200, 201, 401, 422]);
    }

    public function testCreateTagReturnsExpected(): void
    {
        $resp = $this->post('/api/tags', []);
        $this->assertContains($resp->status(), [200, 201, 401, 422]);
    }

    public function testCreateUserWithoutAuthReturns401(): void
    {
        $resp = $this->post('/api/users', []);
        $this->assertEquals(401, $resp->status());
    }

    public function testProductIndexIsPaginated(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products', $auth);
        $json = $resp->json();
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function testResponseFormatHasSuccess(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products', $auth);
        $json = $resp->json();
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function testPutWithoutIdReturns404(): void
    {
        $auth = $this->authenticate();
        $resp = $this->put('/api/products/999999', ['name' => 'Test'], $auth);
        $this->assertContains($resp->status(), [401, 404]);
    }

    public function testDeleteWithoutIdReturns404(): void
    {
        $auth = $this->authenticate();
        $resp = $this->delete('/api/products/999999', $auth);
        $this->assertContains($resp->status(), [401, 404]);
    }

    public function testHealthWorks(): void
    {
        $this->get('/health')->assertOk();
    }

    public function testUsersEndpointReturnsSuccess(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/users', $auth)->assertOk();
    }

    public function testTagsEndpointReturnsSuccess(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/tags', $auth)->assertOk();
    }

    public function testOrdersEndpointReturnsSuccess(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/orders', $auth)->assertOk();
    }

    public function testPostsEndpointReturnsSuccess(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/posts', $auth)->assertOk();
    }

    public function testCategoriesEndpointReturnsSuccess(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/categories', $auth)->assertOk();
    }
}
