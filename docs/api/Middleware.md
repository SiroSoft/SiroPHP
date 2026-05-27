---
title: M id dl ew ar e
description: SiroPHP M id dl ew ar e reference
sidebar_position: 17
sidebar_label: M id dl ew ar e
---

# Middleware API Reference

## Overview

Middleware provides a pipeline for processing HTTP requests before they reach your controllers. Each middleware can inspect, modify, or reject requests.

---

## Built-in Middleware

| Middleware | Class | Purpose |
|-----------|-------|---------|
| Auth | `AuthMiddleware` | JWT authentication, role-based access |
| CORS | `CorsMiddleware` | Cross-Origin Resource Sharing headers |
| CSP | `CspMiddleware` | Content-Security-Policy headers |
| CSRF | `CsrfMiddleware` | CSRF token validation |
| ETag | `EtagMiddleware` | HTTP ETag caching |
| JSON | `JsonMiddleware` | JSON content-type enforcement |
| Metrics | `MetricsMiddleware` | Prometheus metrics collection |
| Audit | `AuditMiddleware` | Request/response audit logging |
| Throttle | `ThrottleMiddleware` | Rate limiting |
| Idempotency | `IdempotencyMiddleware` | Idempotency key support |
| Version | `VersionMiddleware` | API version routing |
| SecurityHeaders | `SecurityHeadersMiddleware` | Security headers (HSTS, etc.) |

---

## Usage

### Apply to Routes

```php
$router->get('/api/users', [UserController::class, 'index'])
    ->middleware(['auth', 'throttle:60,1']);

$router->post('/api/products', [ProductController::class, 'store'])
    ->middleware(['auth:admin', 'json']);
```

### Apply to Groups

```php
$router->group('/api', ['auth', 'cors', 'version'], function (Router $r) {
    $r->resource('products', ProductController::class);
    $r->resource('orders', OrderController::class);
});
```

---

## Creating Custom Middleware

### 1. Generate

```bash
php siro make:middleware LogRequest
```

### 2. Implement

```php
<?php

declare(strict_types=1);

namespace App\Middleware;

use Siro\Core\Request;
use Siro\Core\Response;

final class LogRequestMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        // Before controller
        $start = microtime(true);

        $response = $next($request);

        // After controller
        $duration = (microtime(true) - $start) * 1000;
        Logger::info("{$request->method()} {$request->path()} — {$duration}ms");

        return $response;
    }
}
```

### 3. Register

```php
// routes/api.php
$router->get('/api/users', [UserController::class, 'index'])
    ->middleware([LogRequestMiddleware::class]);
```

---

## Middleware with Parameters

```php
class ThrottleMiddleware
{
    public function handle(Request $request, callable $next, int $maxAttempts, int $decayMinutes): Response
    {
        // $maxAttempts = 60, $decayMinutes = 1
        return $next($request);
    }
}

// Usage: middleware name followed by colon-separated params
->middleware(['throttle:60,1']);
```

---

## Auth Middleware (Built-in)

### Basic Auth

```php
->middleware(['auth']);
```

Validates JWT token from `Authorization: Bearer <token>` header. Sets user on request.

### Role-Based Auth

```php
// Require admin role
->middleware(['auth:admin']);

// Multiple roles allowed
->middleware(['auth:admin,moderator']);
```

### Auth Flow

1. Extract Bearer token from `Authorization` header
2. Decode and verify JWT signature + expiry
3. Validate `token_version` matches database (supports revocation)
4. Check user is active (`status = 1`)
5. Set user data on request via `$request->setUser()`
6. Return 401 for invalid/expired tokens, 403 for inactive accounts

---

## Middleware Pipeline Order

Middleware executes in the order they are defined:

```php
$router->group('/api', [
    SecurityHeadersMiddleware::class,  // 1st — set headers
    CorsMiddleware::class,             // 2nd — CORS
    'version',                          // 3rd — API version
    'etag',                             // 4th — ETag check
    'metrics',                          // 5th — start metrics timer
    'audit',                            // 6th — audit log
], function (Router $r) {
    $r->post('/auth/login', [AuthController::class, 'login'])
        ->middleware(['json', 'throttle:60,1']); // 7th, 8th
});
```
