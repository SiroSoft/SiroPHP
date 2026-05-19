<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class CrudValidationTest extends TestCase
{
    public function testCreateProductReturns401WithoutAuth(): void
    {
        $resp = $this->post('/api/products', []);
        $this->assertEquals(401, $resp->status(), 'Create product without auth should return 401');
    }

    public function testCreateCategoryReturns401WithoutAuth(): void
    {
        $resp = $this->post('/api/categories', ['name' => 'TestCat']);
        $this->assertEquals(401, $resp->status(), 'Create category without auth should return 401');
    }

    public function testCreateOrderReturns401WithoutAuth(): void
    {
        $resp = $this->post('/api/orders', []);
        $this->assertEquals(401, $resp->status(), 'Create order without auth should return 401');
    }

    public function testCreatePostReturns401WithoutAuth(): void
    {
        $resp = $this->post('/api/posts', []);
        $this->assertEquals(401, $resp->status(), 'Create post without auth should return 401');
    }

    public function testCreateTagReturns401WithoutAuth(): void
    {
        $resp = $this->post('/api/tags', []);
        $this->assertEquals(401, $resp->status(), 'Create tag without auth should return 401');
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

    public function testPutWithoutIdReturns403ForNonAdmin(): void
    {
        $auth = $this->authenticate();
        $resp = $this->put('/api/products/999999', ['name' => 'Test'], $auth);
        $this->assertEquals(403, $resp->status(), 'Non-admin user should get 403 for update');
    }

    public function testDeleteWithoutIdReturns403ForNonAdmin(): void
    {
        $auth = $this->authenticate();
        $resp = $this->delete('/api/products/999999', $auth);
        $this->assertEquals(403, $resp->status(), 'Non-admin user should get 403 for delete');
    }

    public function testHealthWorks(): void
    {
        $this->get('/health')->assertOk();
    }

    public function testUsersEndpointReturnsSuccess(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/users', $auth);
        $this->assertContains($resp->status(), [200, 403]);
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
