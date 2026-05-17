# Authentication Guide

## JWT Authentication Flow

SiroPHP uses JWT (JSON Web Tokens) for API authentication. The default algorithm is HS256.

Configuration is in `config/jwt.php` loaded from `.env`:

```env
JWT_SECRET=your_strong_secret_at_least_32_chars
JWT_TTL=3600              # Access token lifetime (seconds)
JWT_REFRESH_TTL=604800    # Refresh token lifetime (seconds)
JWT_ALGORITHM=HS256       # HS256 or RS256
```

### Register

```php
POST /api/auth/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "secret123",
    "password_confirmation": "secret123"
}
```

Response `201`:
```json
{
    "success": true,
    "message": "Register successful",
    "data": {
        "token": "eyJ...",
        "refresh_token": "eyJ...",
        "token_type": "Bearer",
        "expires_in": 3600,
        "user": { "id": 1, "name": "John Doe", "email": "john@example.com" }
    }
}
```

Validation rules: `name` required|min:3|max:120, `email` required|email|max:255, `password` required|min:8|max:255.

### Login

```php
POST /api/auth/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "secret123"
}
```

Response `200` — same format as register. Returns token pair.

Rate-limited: `throttle:60,1` (60 requests per minute). Account locks after multiple failed attempts (configurable via `login_attempts` field).

### Refresh

```php
POST /api/auth/refresh
Content-Type: application/json

{
    "refresh_token": "eyJ..."
}
```

Response `200` — returns new token pair. Old refresh token is revoked (rotation).

### Logout

```php
POST /api/auth/logout
Authorization: Bearer <token>
```

Increments the user's `token_version`, invalidating all existing tokens.

### Get Current User

```php
GET /api/auth/me
Authorization: Bearer <token>
```

Response `200`:
```json
{
    "success": true,
    "data": { "id": 1, "name": "John", "email": "john@example.com", "role": "user" }
}
```

## Middleware

### Auth Middleware

Protect routes by requiring a valid JWT:

```php
// Apply to individual routes
$router->get('/api/users', [UserController::class, 'index'])
    ->middleware(['auth']);

// Apply to route groups
$router->group('/api', ['auth'], function (Router $router) {
    $router->resource('products', ProductController::class);
});

// In TestCase, aliases are set:
// 'auth' => \App\Middleware\AuthMiddleware::class
```

The middleware:
1. Extracts Bearer token from `Authorization` header
2. Decodes and verifies JWT signature and expiry
3. Validates `token_version` matches database (allows token revocation)
4. Checks user is active (status = 1)
5. Sets user data on request via `$request->setUser()`
6. Returns 401 for invalid/expired tokens, 403 for inactive accounts

### Role-Based Access

Pass required roles to the auth middleware:

```php
// Route requires 'admin' role
$router->get('/api/admin/users', [AdminController::class, 'index'])
    ->middleware(['auth:admin']);

// Multiple roles allowed
$router->put('/api/users/{id}', [UserController::class, 'update'])
    ->middleware(['auth:admin,moderator']);

// Check role in controller
$user = $request->user();
$role = $user['role'] ?? '';
if ($role !== 'admin') {
    return Response::error('Forbidden', 403);
}
```

### Throttle Middleware

```php
// Throttle: max 60 requests per 1 minute
$router->post('/api/auth/login', [AuthController::class, 'login'])
    ->middleware(['throttle:60,1']);

// Syntax: throttle:<max_attempts>,<decay_minutes>
```

## API Keys

For machine-to-machine authentication, pass API keys via header:

```php
// In custom middleware
$apiKey = $request->header('x-api-key', '');
if ($apiKey !== $_ENV['API_KEY']) {
    return Response::error('Unauthorized', 401);
}
```

## Email Verification

```php
// Routes exist for email verification flow
POST /api/auth/verify-email      # Verify with token
POST /api/auth/forgot-password   # Request reset link
POST /api/auth/reset-password    # Reset with token
```

## Security Best Practices

- Generate a strong `JWT_SECRET` with `php siro key:generate`.
- Use short `JWT_TTL` (15-60 minutes) and longer `JWT_REFRESH_TTL` (7 days).
- Always use HTTPS in production.
- Store tokens securely on the client (httpOnly cookies for web, secure storage for mobile).
- The throttle middleware mitigates brute-force attacks.
- Account lockout after failed login attempts is built in.
