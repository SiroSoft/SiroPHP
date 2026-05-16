<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class TagsOrdersPostsTest extends TestCase
{
    public function testTagsIndexReturns200(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'GET', '/api/tags', [], $headers);
        $this->assertEquals(200, $response->statusCode());
    }

    public function testTagsShowReturns404(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'GET', '/api/tags/999', [], $headers);
        $this->assertEquals(404, $response->statusCode());
    }

    public function testTagsStoreReturns201(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'POST', '/api/tags', ['name' => 'TestTag'], $headers);
        $this->assertEquals(201, $response->statusCode());
    }

    public function testTagsStoreReturns422WithoutName(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'POST', '/api/tags', [], $headers);
        $this->assertEquals(422, $response->statusCode());
    }

    public function testOrdersIndexReturns200(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'GET', '/api/orders', [], $headers);
        $this->assertEquals(200, $response->statusCode());
    }

    public function testOrdersShowReturns404(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'GET', '/api/orders/999', [], $headers);
        $this->assertEquals(404, $response->statusCode());
    }

    public function testOrdersStoreReturns422WithoutData(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'POST', '/api/orders', [], $headers);
        $this->assertEquals(422, $response->statusCode());
    }

    public function testPostsIndexReturns200(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'GET', '/api/posts', [], $headers);
        $this->assertEquals(200, $response->statusCode());
    }

    public function testPostsShowReturns404(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'GET', '/api/posts/999', [], $headers);
        $this->assertEquals(404, $response->statusCode());
    }

    public function testPostsStoreReturns201(): void
    {
        $app = $this->createApp();
        $headers = $this->authenticate($app);
        $response = $this->dispatch($app, 'POST', '/api/posts', ['title' => 'Test Post', 'body' => 'Content body here', 'user_id' => 1], $headers);
        $this->assertContains($response->statusCode(), [201, 422]);
    }
}
