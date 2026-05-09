# Migration Guide: v0.20 → v0.21

## Overview

v0.21.0 focuses on **security hardening**, **type safety**, and **developer experience**.
Key changes: MiddlewareInterface contract, Controller base class, PHPStan Level max.

## Upgrade Steps

### 1. Update Dependencies

```bash
composer require sirosoft/core:^0.21
```

### 2. Update Middleware Classes

**Before (v0.20):**
```php
final class MyMiddleware
{
    public function handle(Request $request, callable $next): mixed
```

**After (v0.21):**
```php
use Siro\Core\Middleware\MiddlewareInterface;

final class MyMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
```

All core middleware classes already implement MiddlewareInterface.

### 3. (Optional) Use Controller Base Class

**Before (v0.20):**
```php
use Siro\Core\Request;
use Siro\Core\Response;

final class PostController
{
    public function index(Request $request): Response
    {
        return Response::paginated($data, $meta, 'Post list');
    }
}
```

**After (v0.21):**
```php
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

final class PostController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->paginated($data, $meta, 'Post list');
        // Also available: $this->success(), $this->error(),
        // $this->created(), $this->noContent(), $this->validate()
    }
}
```

### 4. Environment Changes

| Variable | v0.20 | v0.21 | Notes |
|----------|-------|-------|-------|
| `APP_DEBUG` | `true` | `false` | Security hardening |
| `CORS_ALLOWED_ORIGINS` | `*` | Restricted to localhost | Must configure for production |
| `JWT_SECRET` | Old key | **Rotated** | Generate new: `php siro key:generate` |

### 5. Log Sanitization

Enable in `.env`:
```env
LOG_SANITIZE_HEADERS=authorization,cookie,x-api-key,session-id
LOG_SANITIZE_BODY=password,token,otp,secret,credit_card,card_number,cvv,pin,ssn
LOG_SANITIZE_QUERY=token,key,secret,api_key,code
```

### 6. PHPStan (Optional Upgrade to Level 7)

Update `phpstan.neon`:
```neon
parameters:
    level: 7  # was 6
```

## Breaking Changes

| Change | Impact | Migration |
|--------|--------|-----------|
| `Cache::requestStatus()` | Now returns `array`, was `string` | Check `['status']` key |
| Router OPTIONS handler | Now respects `CORS_ALLOWED_ORIGINS` env | Configure CORS for your domain |
| `Response::download()` | Sanitizes newlines in filename | No action needed |
| `Validator::min`/`max` | Now uses strict type checking | Values are compared as is_int/is_string/is_float |

## New Features

### MiddlewareInterface
```php
use Siro\Core\Middleware\MiddlewareInterface;
```
All middleware must implement this interface. Provides IDE auto-complete and type safety.

### Controller Base Class
```php
use Siro\Core\Controller;
```
Provides `success()`, `error()`, `created()`, `noContent()`, `paginated()`, `validate()`, `input()`, `param()`, `query()`, `user()` helper methods.

### Event::currentEvent()
```php
Event::on('user.created', function ($user) {
    echo Event::currentEvent(); // 'user.created'
});
```

### Lang::count()
```php
$count = Lang::count('validation'); // Number of validation messages
```

## Testing

v0.21 adds 59 new tests:

| Component | Tests |
|-----------|-------|
| AuthMiddleware | 8 |
| ThrottleMiddleware | 6 |
| CsrfMiddleware | 13 |
| IdempotencyMiddleware | 10 |
| JWT | 20 |
| DB Integration (MySQL+PG) | 7 |

**Total**: 863 tests (core) + 414 tests (skeleton) = 1277 tests

## Support

- Issues: https://github.com/SiroSoft/siro-core/issues
- Source: https://github.com/SiroSoft/siro-core
