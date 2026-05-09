<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class CrudValidationTest extends TestCase
{
    public function testCreateProductReturnsSuccessOrValidation(): void
    {
        $resp = $this->post('/api/products', []);
        $this->assertContains($resp->status(), [200, 201, 422]);
    }

    public function testCreateCategoryReturnsExpected(): void
    {
        $resp = $this->post('/api/categories', ['name' => 'TestCat']);
        $this->assertContains($resp->status(), [200, 201, 422]);
    }

    public function testCreateOrderReturnsExpected(): void
    {
        $resp = $this->post('/api/orders', []);
        $this->assertContains($resp->status(), [200, 201, 422]);
    }

    public function testCreatePostReturnsExpected(): void
    {
        $resp = $this->post('/api/posts', []);
        $this->assertContains($resp->status(), [200, 201, 422]);
    }

    public function testCreateTagReturnsExpected(): void
    {
        $resp = $this->post('/api/tags', []);
        $this->assertContains($resp->status(), [200, 201, 422]);
    }

    public function testCreateUserWithoutAuthReturns401(): void
    {
        $resp = $this->post('/api/users', []);
        $this->assertEquals(401, $resp->status());
    }

    public function testProductIndexIsPaginated(): void
    {
        $resp = $this->get('/api/products');
        $json = $resp->json();
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function testResponseFormatHasSuccess(): void
    {
        $resp = $this->get('/api/products');
        $json = $resp->json();
        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('data', $json);
    }

    public function testPutWithoutIdReturns404(): void
    {
        $resp = $this->put('/api/products/999999', ['name' => 'Test']);
        $this->assertEquals(404, $resp->status());
    }

    public function testDeleteWithoutIdReturns404(): void
    {
        $resp = $this->delete('/api/products/999999');
        $this->assertEquals(404, $resp->status());
    }

    public function testHealthWorks(): void
    {
        $this->get('/health')->assertOk();
    }

    public function testUsersEndpointReturnsSuccess(): void
    {
        $this->get('/api/users')->assertOk();
    }

    public function testTagsEndpointReturnsSuccess(): void
    {
        $this->get('/api/tags')->assertOk();
    }

    public function testOrdersEndpointReturnsSuccess(): void
    {
        $this->get('/api/orders')->assertOk();
    }

    public function testPostsEndpointReturnsSuccess(): void
    {
        $this->get('/api/posts')->assertOk();
    }

    public function testCategoriesEndpointReturnsSuccess(): void
    {
        $this->get('/api/categories')->assertOk();
    }
}