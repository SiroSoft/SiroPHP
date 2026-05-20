# Request API Reference

## Overview

The Request object represents the incoming HTTP request and provides methods to access input data, headers, files, and authenticated user.

```php
use Siro\Core\Request;

class ProductController
{
    public function store(Request $request): Response
    {
        // Access data
        $name = $request->string('name');
        $price = $request->float('price');
    }
}
```

---

## Input Methods

### Get Input Values

```php
// All input as array
$all = $request->all();

// Single value with type
$name    = $request->string('name');          // string
$price   = $request->float('price');           // float
$age     = $request->int('age');               // int
$active  = $request->bool('active');           // bool
$tags    = $request->array('tags');            // array
$raw     = $request->input('key', 'default');  // mixed

// Query string only
$page   = $request->queryInt('page', 1);
$search = $request->queryString('search', '');
$filter = $request->query('sort', 'id');

// Check if key exists
if ($request->has('email')) { ... }
```

### Nested Input

```php
// Dot notation
$city = $request->input('address.city');
$tags = $request->array('items.*.id');
```

---

## Validation

### Validate Request

```php
$validated = $request->validate([
    'email' => 'required|email|max:255',
    'password' => 'required|min:8|max:255',
    'name' => 'required|min:3|max:120',
]);

// On failure: throws ValidationException → 422 response
// On success: returns validated data array
```

---

## Route Parameters

```php
// GET /api/products/{id}
$id = $request->param('id');         // string|null
$id = $request->paramInt('id');      // int (0 if missing)
$id = $request->paramString('slug'); // string|null
```

---

## Headers

```php
$token    = $request->header('Authorization');      // string|null
$ct       = $request->header('Content-Type', 'json'); // with default
$accept   = $request->header('Accept', '*/*');
$userAgent = $request->userAgent();                   // shortcut
$ip       = $request->ip();                           // client IP
$method   = $request->method();                       // GET, POST, ...
$path     = $request->path();                         // /api/products
```

---

## Files

```php
// Single file
$file = $request->file('avatar');
if ($file !== null && $file->isValid()) {
    $path = $file->store('avatars');
    $name = $file->getClientOriginalName();
    $size = $file->getSize();
    $mime = $file->getMimeType();
}

// Multiple files
$files = $request->file('gallery');
foreach ($files as $file) {
    $path = $file->store('gallery');
}
```

---

## Authentication

```php
// Get authenticated user
$user = $request->user();
$userId = $user['id'] ?? 0;
$role   = $user['role'] ?? 'guest';

// Check if authenticated
if ($request->user() !== null) { ... }

// Set user (called by AuthMiddleware)
$request->setUser($userData);
```

---

## Request Context

```php
// Trace ID for debugging
$traceId = $request->traceId();

// Request timing
$startTime = $request->server('REQUEST_TIME_FLOAT');

// Full URL
$url = $request->fullUrl();

// Scheme
$isHttps = $request->isSecure();
```

---

## Available Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `all()` | `array` | All input data |
| `input(string $key, mixed $default)` | `mixed` | Get input value |
| `string(string $key, string $default)` | `string` | Get string value |
| `int(string $key, int $default)` | `int` | Get integer value |
| `float(string $key, float $default)` | `float` | Get float value |
| `bool(string $key, bool $default)` | `bool` | Get boolean value |
| `array(string $key, array $default)` | `array` | Get array value |
| `query(string $key, string $default)` | `string` | Get query param |
| `queryInt(string $key, int $default)` | `int` | Get query param as int |
| `queryString(string $key, string $default)` | `string` | Get query param as string |
| `has(string $key)` | `bool` | Check if key exists |
| `param(string $key)` | `string\|null` | Get route parameter |
| `paramInt(string $key)` | `int` | Get route param as int |
| `paramString(string $key)` | `string\|null` | Get route param as string |
| `header(string $key, string $default)` | `string\|null` | Get request header |
| `method()` | `string` | HTTP method |
| `path()` | `string` | Request path |
| `ip()` | `string` | Client IP |
| `userAgent()` | `string` | User agent |
| `user()` | `array\|null` | Authenticated user |
| `setUser(array $user)` | `void` | Set authenticated user |
| `file(string $key)` | `UploadedFile\|null` | Get uploaded file |
| `validate(array $rules)` | `array` | Validate and return |
| `isSecure()` | `bool` | Check HTTPS |
| `fullUrl()` | `string` | Full request URL |
