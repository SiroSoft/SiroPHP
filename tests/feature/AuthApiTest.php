<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class AuthApiTest extends TestCase
{
    public function testHealthEndpoint(): void
    {
        $this->get('/health')->assertOk()->assertJson(['success' => true]);
    }

    public function testRootEndpoint(): void
    {
        $this->get('/')->assertOk();
    }

    public function testRegisterEndpoint(): void
    {
        $resp = $this->post('/api/auth/register', [
            'name' => 'Test',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => 'secret123',
        ]);
        $this->assertContains($resp->status(), [200, 201], 'Registration with valid data should succeed');
    }

    public function testLoginWithEmptyDataReturnsValidation(): void
    {
        $resp = $this->post('/api/auth/login', []);
        $this->assertEquals(422, $resp->status());
    }

    public function testLoginReturns422ForInvalidEmail(): void
    {
        $resp = $this->post('/api/auth/login', ['email' => 'invalid', 'password' => '123456']);
        $this->assertEquals(422, $resp->status());
    }

    public function testProductsEndpoint(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/products', $auth)->assertOk();
    }

    public function testProductsShowReturns404(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/products/99999', $auth);
        $this->assertEquals(404, $resp->status());
    }

    public function testCategoriesEndpoint(): void
    {
        $auth = $this->authenticate();
        $this->get('/api/categories', $auth)->assertOk();
    }

    public function testUsersEndpoint(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/users', $auth);
        $this->assertContains($resp->status(), [200, 403]);
    }

    public function testUsersShowReturns403ForNonAdmin(): void
    {
        $auth = $this->authenticate();
        $resp = $this->get('/api/users/99999', $auth);
        $this->assertEquals(403, $resp->status(), 'Non-admin user should get 403 for user detail');
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

    public function testCreateProduct(): void
    {
        $resp = $this->post('/api/products', ['name' => 'Test']);
        $this->assertEquals(401, $resp->status(), 'Create product without auth should return 401');
    }

    public function testUpdateProduct(): void
    {
        $resp = $this->put('/api/products/1', ['name' => 'Updated']);
        $this->assertEquals(401, $resp->status(), 'Update product without auth should return 401');
    }

    public function testDeleteProduct(): void
    {
        $resp = $this->delete('/api/products/1');
        $this->assertEquals(401, $resp->status(), 'Delete product without auth should return 401');
    }

    public function testHealthResponseStatus(): void
    {
        $resp = $this->get('/health');
        $json = $resp->json();
        $this->assertEquals('healthy', $json['data']['status'] ?? '');
    }
}
