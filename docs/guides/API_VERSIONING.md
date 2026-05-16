# API Versioning Guide

## Overview

SiroPHP supports API versioning through the `VersionMiddleware`. You can maintain multiple API versions simultaneously, allowing clients to migrate at their own pace.

## Route Prefix Versioning

Register versioned route prefixes:

```php
use Siro\Core\Middleware\VersionMiddleware;

// Register version-to-prefix mappings
VersionMiddleware::register(1, '/api/v1');
VersionMiddleware::register(2, '/api/v2');

// In routes/api.php, the 'version' middleware is already applied
// to the /api group. Routes will be accessible under:
//   /api/v1/products (version 1)
//   /api/v2/products (version 2)
```

Routes registered after `VersionMiddleware::register()` are automatically duplicated under the versioned prefix.

### Defining Version-Specific Routes

```php
// Both endpoints accessible at /api/v1/products and /api/v2/products
$router->resource('products', ProductController::class, ['auth']);

// Create separate controllers for each version:
// v1: /api/v1/orders
// v2: /api/v2/orders (with new fields)

// Or use the same controller with conditional logic
```

## Header-Based Versioning

Clients can specify the version via the `Accept` header:

```
Accept: application/vnd.siro.v1+json
Accept: application/vnd.siro.v2+json
```

The middleware reads the header and routes to the appropriate version.

## Accessing in Controllers

Access request metadata to implement version-aware logic:

```php
public function index(Request $request): Response
{
    // Check version from URL or header
    $path = $request->path();
    if (str_contains($path, '/v2/')) {
        // Return v2 response format
    }

    // Default v1 response
}
```

## Middleware Registration

The version middleware is registered alongside other middleware in `TestCase::createApp()` or the application bootstrap:

```php
Router::setMiddlewareAliases([
    'auth' => \App\Middleware\AuthMiddleware::class,
    'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
    'cors' => \Siro\Core\Middleware\CorsMiddleware::class,
    'json' => \Siro\Core\Middleware\JsonMiddleware::class,
    'version' => \Siro\Core\Middleware\VersionMiddleware::class,
    'etag' => \Siro\Core\Middleware\EtagMiddleware::class,
    'metrics' => \Siro\Core\Middleware\MetricsMiddleware::class,
]);
```

## Best Practices

- Define all version mappings at the start of `routes/api.php` before any routes.
- Maintain backward compatibility within a major version — only add fields, don't remove them.
- Deprecate old versions gradually: announce deprecation in response headers, then remove when usage drops to zero.
- Use a sunset policy (e.g. support each version for at least 6 months after replacement is stable).
- Version your database schema logically — don't create separate databases per API version.
