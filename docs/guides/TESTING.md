# Testing Guide

## Test Structure

Tests live under `tests/` organized by type:

```
tests/
  unit/          # Isolated component tests (no DB)
  integration/   # DB-dependent tests
  feature/       # Full HTTP request/response tests
  edge_case/     # Boundary & fuzz tests
  cli/           # Console command tests
  TestCase.php   # Base test class
```

## Test Helpers

### HTTP Methods

```php
// GET request
$response = $this->get('/api/users');

// POST with body
$response = $this->post('/api/products', ['name' => 'Laptop', 'price' => 1500]);

// PUT with body
$response = $this->put('/api/products/1', ['name' => 'Updated']);

// DELETE
$response = $this->delete('/api/products/1');

// Custom headers
$response = $this->get('/api/users', ['X-Custom' => 'value']);
```

### Authentication

```php
// Get Bearer token headers for authenticated requests
$auth = $this->authenticate();
$response = $this->get('/api/products', $auth);

// Reuse across tests
protected function setUp(): void
{
    parent::setUp();
    $app = $this->createApp();
    $this->authHeaders = $this->authenticate($app);
}
```

## Fluent Assertions

`get()`, `post()`, `put()`, `delete()` return a `TestResponse` instance:

```php
// Status assertions
$this->get('/health')->assertOk();                           // 200
$this->post('/api/products')->assertCreated();               // 201
$this->get('/api/empty')->assertNoContent();                 // 204
$this->get('/api/protected')->assertUnauthorized();           // 401
$this->get('/api/admin')->assertForbidden();                 // 403
$this->get('/api/missing')->assertNotFound();                // 404
$this->post('/api/invalid')->assertValidationError();        // 422
$this->get('/api/broken')->assertServerError();              // 500
$this->get('/api/data')->assertStatus(200);                  // Custom status

// JSON assertions
$this->get('/health')
    ->assertJson(['success' => true, 'message' => 'OK']);

// Deep path assertion
$this->get('/api/products?page=1')
    ->assertJsonPath('meta.page', 1);

// Header assertion
$this->get('/api/data')
    ->assertHeader('Content-Type', 'application/json');

// Get decoded JSON body
$json = $this->get('/api/products')->json();
$data = $json['data'] ?? [];
```

## Database Assertions

```php
// Assert row exists
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);

// Assert row is missing
$this->assertDatabaseMissing('users', ['email' => 'nonexistent@test.com']);

// Works with SQLite, MySQL, PostgreSQL automatically
```

## Writing Tests

### CRUD Tests

```php
public function testCreateProduct(): void
{
    $resp = $this->post('/api/products', [
        'name' => 'Laptop',
        'sku' => 'LAP-001',
        'price' => '1500.00',
        'stock' => '100',
        'category' => 'Electronics',
    ], $this->authHeaders);
    $resp->assertCreated();
    $this->assertDatabaseHas('products', ['name' => 'Laptop']);
}

public function testListProducts(): void
{
    $resp = $this->get('/api/products', $this->authHeaders);
    $resp->assertOk();
    $body = $resp->json();
    $this->assertArrayHasKey('data', $body);
    $this->assertArrayHasKey('meta', $body);
}

public function testShowProduct(): void
{
    $resp = $this->get('/api/products/1', $this->authHeaders);
    $this->assertContains($resp->status(), [200, 404]);
}

public function testUpdateProduct(): void
{
    $resp = $this->put('/api/products/1', [
        'name' => 'Updated Laptop',
    ], $this->authHeaders);
    $this->assertContains($resp->status(), [200, 404]);
}

public function testDeleteProduct(): void
{
    $resp = $this->delete('/api/products/999999', $this->authHeaders);
    $this->assertContains($resp->status(), [200, 404]);
}
```

### Auth Tests

```php
public function testRegisterSuccess(): void
{
    $resp = $this->post('/api/auth/register', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'secret123',
    ]);
    $this->assertContains($resp->status(), [200, 201]);
}

public function testLoginValidation(): void
{
    $resp = $this->post('/api/auth/login', []);
    $resp->assertValidationError();
}

public function testProtectedEndpointWithoutAuth(): void
{
    $resp = $this->get('/api/products');
    $resp->assertUnauthorized();
}
```

### Validation Tests

```php
public function testValidationFailsOnMissingFields(): void
{
    $resp = $this->post('/api/products', [], $this->authHeaders);
    $resp->assertStatus(422);
}

public function testExtraFieldsAreIgnored(): void
{
    $resp = $this->post('/api/products', [
        'name' => 'Test',
        'nonexistent_field' => 'ignored',
    ], $this->authHeaders);
    $this->assertContains($resp->status(), [200, 201]);
}
```

### Edge Case Tests

```php
public function testSpecialCharactersInInput(): void
{
    $auth = $this->authenticate();
    $resp = $this->post('/api/products', [
        'name' => '<script>alert("xss")</script>',
        'price' => 10,
    ], $auth);
    $this->assertContains($resp->status(), [200, 201]);
}

public function testNegativePageNumber(): void
{
    $auth = $this->authenticate();
    $resp = $this->get('/api/products?page=-1', $auth);
    $resp->assertOk();
}

public function testEmptyRequestBody(): void
{
    $resp = $this->post('/api/auth/login', []);
    $resp->assertValidationError();
}

public function testUnicodeEmail(): void
{
    $resp = $this->post('/api/auth/register', [
        'name' => 'Unicode',
        'email' => 'user@münchen.de',
        'password' => 'secret123',
    ]);
    $this->assertContains($resp->status(), [200, 201, 422]);
}

public function testZeroPerPage(): void
{
    $auth = $this->authenticate();
    $resp = $this->get('/api/products?per_page=0', $auth);
    $resp->assertOk();
}
```

## Code Generation

```bash
# Generate test file
php siro make:test ProductApi

# Generate full CRUD with tests
php siro make:crud products
```

## Running Tests

```bash
# Run all tests
php siro test

# Run via PHPUnit directly
php vendor/bin/phpunit

# Run specific suite
php vendor/bin/phpunit --testsuite Unit
php vendor/bin/phpunit --testsuite Integration
php vendor/bin/phpunit --testsuite Feature
php vendor/bin/phpunit --testsuite EdgeCase

# Filter by test name
php vendor/bin/phpunit --filter testCreateProduct

# Run single file
php vendor/bin/phpunit tests/feature/ProductTest.php
```

## Best Practices

- Use `$this->authenticate()` to obtain auth headers for protected endpoints.
- Prefer `assertContains()` for status codes when multiple outcomes are valid.
- Assert JSON structure with `assertArrayHasKey()` and `assertJsonPath()`.
- Use `assertDatabaseHas()`/`assertDatabaseMissing()` to verify DB state.
- Write edge case tests for empty input, special characters, boundary values.
- Each test method should test exactly one behavior.
- Use descriptive test method names: `testCreateProductFailsWithoutName`.
- Tests auto-wrap in a transaction and roll back after each test.
