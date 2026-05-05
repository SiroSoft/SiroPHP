<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class CategoriesTest extends TestCase
{
    public function testIndexReturns200(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/api/categories');
        $this->assertEquals(200, $response->statusCode());
    }

    public function testShowReturns404ForInvalidId(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'GET', '/api/categories/999');
        $this->assertEquals(404, $response->statusCode());
    }

    public function testStoreReturns201WithValidData(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'POST', '/api/categories', ['name' => 'Test Category', 'slug' => 'test-category'], ['content-type' => 'application/json']);
        $this->assertEquals(201, $response->statusCode());
    }

    public function testStoreReturns422WithoutRequiredFields(): void
    {
        $app = $this->createApp();
        $response = $this->dispatch($app, 'POST', '/api/categories', [], ['content-type' => 'application/json']);
        $this->assertEquals(422, $response->statusCode());
    }
}
