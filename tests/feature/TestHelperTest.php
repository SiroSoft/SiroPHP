<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;

final class TestHelperTest extends TestCase
{
    public function testGetHelperReturnsTestResponse(): void
    {
        $response = $this->get('/');
        $this->assertInstanceOf(\App\Tests\TestResponse::class, $response);
    }

    public function testAssertOk(): void
    {
        $this->get('/')->assertOk();
    }

    public function testAssertNotFound(): void
    {
        $this->get('/api/nonexistent')->assertNotFound();
    }

    public function testAssertUnauthorized(): void
    {
        $this->get('/api/auth/me')->assertUnauthorized();
    }

    public function testAssertValidationError(): void
    {
        $this->post('/api/auth/login', [])->assertValidationError();
    }

    public function testAssertJson(): void
    {
        $this->get('/')
            ->assertJson(['success' => true])
            ->assertJson(['message' => 'Welcome']);
    }

    public function testAssertJsonPath(): void
    {
        $this->get('/')
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Siro API Framework');
    }

    public function testAssertCreated(): void
    {
        $response = $this->post('/api/auth/register', [
            'name' => 'Helper Test User',
            'email' => 'helper-' . uniqid() . '@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);
        $response->assertCreated();
    }

    public function testAssertStatus(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/api/nonexistent')->assertStatus(404);
    }

    public function testAssertDatabaseHas(): void
    {
        $email = 'db-has-' . uniqid() . '@test.com';
        $this->post('/api/auth/register', [
            'name' => 'DB Has Test',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => $email]);
    }

    public function testAssertDatabaseMissing(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'nonexistent-' . uniqid() . '@test.com']);
    }

    public function testAuthFlow(): void
    {
        $email = 'flow-' . uniqid() . '@test.com';
        $this->post('/api/auth/register', [
            'name' => 'Flow Test',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => $email]);

        $this->post('/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ])->assertOk();

        $this->post('/api/auth/login', [
            'email' => $email,
            'password' => 'wrongpass',
        ])->assertUnauthorized();
    }

    public function testProductCrudViaHelpers(): void
    {
        $response = $this->get('/api/products');
        $response->assertOk();
        $response->assertJson(['success' => true]);

        $email = 'crud-' . uniqid() . '@test.com';
        $this->post('/api/auth/register', [
            'name' => 'Crud Test',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertCreated();

        $login = $this->post('/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);
        $login->assertOk();
        $loginJson = $login->json();
        $loginData = $loginJson['data'] ?? [];
        /** @var array<string, mixed> $loginData */
        $rawToken = $loginData['token'] ?? '';
        /** @var string $rawToken */
        $token = $rawToken;

        $created = $this->post('/api/products', [
            'name' => 'Helper Product',
            'price' => 29.99,
        ], ['Authorization' => 'Bearer ' . $token]);
        $created->assertCreated();
        $created->assertJsonPath('data.name', 'Helper Product');
    }
}
