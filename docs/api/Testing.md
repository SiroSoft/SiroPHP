---
title: T es ti ng
description: SiroPHP T es ti ng reference
sidebar_position: 31
sidebar_label: T es ti ng
---

# Testing API Reference

## Overview

Siro provides a PHPUnit base test class with HTTP helpers, in-memory SQLite, and authentication shortcuts.

```php
use App\Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_list_products(): void
    {
        $response = $this->get('/api/products');
        $this->assertSame(200, $response->statusCode());
    }
}
```

---

## Test Structure

```
tests/
├── TestCase.php           # Base test class
├── unit/                  # Isolated tests (no DB)
├── integration/           # DB-dependent tests
├── feature/               # HTTP endpoint tests
├── edge_case/             # Boundary tests
└── cli/                   # CLI command tests
```

---

## HTTP Test Helpers

### Basic Requests

```php
// GET
$response = $this->get('/api/users');

// POST with JSON body
$response = $this->post('/api/products', [
    'name' => 'Laptop',
    'price' => 999,
]);

// PUT
$response = $this->put('/api/products/1', [
    'name' => 'Updated Laptop',
]);

// DELETE
$response = $this->delete('/api/products/1');
```

### Authenticated Requests

```php
// Login and get auth headers
$headers = $this->authenticate();

// Use in requests
$response = $this->get('/api/orders', $headers);
$response = $this->post('/api/orders', $body, $headers);
```

---

## Assertions

```php
$response = $this->get('/api/users');

// Status code
$this->assertSame(200, $response->statusCode());

// JSON body
$json = $response->json();
$this->assertTrue($json['success']);
$this->assertArrayHasKey('data', $json);

// Specific data
$data = $json['data'] ?? [];
$this->assertNotEmpty($data);
$this->assertSame('John', $data[0]['name']);
```

---

## Test Case Methods

| Method | Description |
|--------|-------------|
| `get(string $path, array $headers)` | GET request |
| `post(string $path, array $body, array $headers)` | POST request |
| `put(string $path, array $body, array $headers)` | PUT request |
| `delete(string $path, array $headers)` | DELETE request |
| `dispatch(App $app, string $method, string $path, array $body, array $headers)` | Raw HTTP dispatch |
| `responseJson(Response $response)` | Decode response to array |
| `authenticate(?App $app)` | Login and return auth headers |
| `createApp()` | Bootstrap app instance |

---

## Database Testing

Tests use SQLite in-memory with transaction rollback between tests:

```php
class ProductTest extends TestCase
{
    public function test_create_product(): void
    {
        // Database is automatically set up
        // Each test runs in a transaction
        // Transaction is rolled back after test

        $response = $this->post('/api/products', [
            'name' => 'Test Product',
            'price' => 99.99,
        ]);

        $this->assertSame(201, $response->statusCode());
    }
}
```

---

## Custom Test Response

The `TestResponse` class provides helpers:

```php
$response = $this->get('/api/users');

$response->statusCode();     // int
$response->json();           // array
$response->body();           // string
$response->header('X-Trace-ID');  // string|null
$response->assertStatus(200);
$response->assertJson(['success' => true]);
$response->assertSee('data');
```

---

## Running Tests

```bash
# All tests
php vendor/bin/phpunit

# By suite
php vendor/bin/phpunit --testsuite=Unit
php vendor/bin/phpunit --testsuite=Feature
php vendor/bin/phpunit --testsuite=Integration
php vendor/bin/phpunit --testsuite=EdgeCase

# Siro CLI
php siro test
php siro test --coverage
php siro test --filter=Product
```

---

## Factory Support

```php
// Define factory
Product::factory()->create([
    'name' => 'Test Product',
    'price' => 49.99,
]);

// Create multiple
Product::factory()->count(10)->create();

// With relations
$product = Product::factory()
    ->has(Category::factory())
    ->create();
```
