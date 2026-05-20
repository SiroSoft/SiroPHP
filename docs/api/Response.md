# Response API Reference

## Overview

The Response object represents the HTTP response sent back to the client. It provides static factory methods for common response patterns.

```php
use Siro\Core\Response;
```

---

## Success Responses

```php
// 200 OK
Response::success($data, 'Operation successful');

// 201 Created
Response::created($data, 'Resource created');

// 204 No Content
Response::noContent();

// Paginated response
Response::paginated(
    UserResource::collection($users['data']),
    $users['meta'],
    'Users retrieved'
);
```

### Response Format

```json
{
    "success": true,
    "message": "Operation successful",
    "data": { "id": 1, "name": "John" },
    "meta": { "page": 1, "per_page": 20, "total": 50, "last_page": 3 }
}
```

---

## Error Responses

```php
// 400 Bad Request
Response::error('Validation failed', 400);

// 401 Unauthorized
Response::error('Invalid credentials', 401);

// 403 Forbidden
Response::error('Forbidden', 403);

// 404 Not Found
Response::error('Resource not found', 404);

// 422 Validation Error (with field errors)
Response::error('Validation failed', 422, [
    'email' => ['Email has already been taken'],
    'name' => ['Name is required'],
]);

// 429 Too Many Requests
Response::error('Too many attempts', 429);

// 500 Internal Server Error
Response::error('Internal server error', 500);
```

### Error Format

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": { "email": ["Email has already been taken"] }
}
```

---

## Raw Responses

```php
// Raw string
Response::raw('<html><body>OK</body></html>', 'text/html');

// JSON from array
Response::json(['custom' => 'format']);

// No content
Response::noContent();  // 204
```

---

## Headers & Status

```php
// Custom status
$response = Response::success($data, 'OK');
$response->setStatusCode(201);

// Headers
$response->setHeader('X-Custom', 'value');
$response->setHeader('X-RateLimit-Remaining', '50');
```

---

## Controller Helper Methods

When using the `Controller` base class:

```php
class ProductController extends Controller
{
    public function index(): Response
    {
        return $this->success($data, 'OK');
    }

    public function store(): Response
    {
        return $this->created($resource, 'Created');
    }

    public function show(): Response
    {
        return $this->success($resource, 'Fetched');
    }

    public function update(): Response
    {
        return $this->success($resource, 'Updated');
    }

    public function delete(): Response
    {
        return $this->noContent();
    }

    public function error(string $message, int $code, array $errors = []): Response
    {
        return Response::error($message, $code, $errors);
    }

    public function paginated(array $data, array $meta, string $message): Response
    {
        return Response::paginated($data, $meta, $message);
    }
}
```

---

## Available Methods

| Method | Status | Description |
|--------|--------|-------------|
| `success(mixed $data, string $message)` | 200 | Success response |
| `created(mixed $data, string $message)` | 201 | Resource created |
| `noContent()` | 204 | No content |
| `paginated(array $data, array $meta, string $message)` | 200 | Paginated list |
| `error(string $message, int $code, array $errors)` | * | Error response |
| `raw(string $content, string $contentType)` | 200 | Raw text response |
| `json(array $data)` | 200 | Custom JSON |
| `setStatusCode(int $code)` | — | Set HTTP status |
| `setHeader(string $key, string $value)` | — | Set response header |
| `send()` | — | Send response to client |
