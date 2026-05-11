# Siro Framework Documentation

**Version:** 0.22.0 | **PHP:** >= 8.2 | **License:** MIT

---

## Quick Start

```bash
composer create-project sirosoft/api my-api
cd my-api
cp .env.example .env
php siro key:generate
php siro migrate
php siro serve
```

Visit `http://localhost:8080` — your API is live.

---

## Architecture

```
my-api/
├── app/
│   ├── Controllers/    # Request handlers
│   ├── Models/         # Database models (ORM)
│   ├── Services/       # Business logic layer
│   ├── Repositories/   # Data access layer
│   ├── Middleware/      # Auth, CORS, Security headers, Rate limiting
│   ├── Resources/      # Response transformers
│   ├── Jobs/           # Queue jobs
│   ├── Mails/          # Email templates
│   ├── Events/         # Event classes
│   └── Exceptions/     # Custom exceptions
├── config/             # Configuration files
├── database/
│   ├── migrations/     # Database migrations
│   └── seeds/          # Data seeders
├── routes/
│   ├── api.php         # API route definitions
│   └── schedule.php    # Scheduled tasks
├── storage/            # Cache, logs, uploads
└── tests/              # PHPUnit tests
```

---

## Routes

Define API routes in `routes/api.php`:

```php
$app->router->get('/api/products', [ProductController::class, 'index']);
$app->router->post('/api/products', [ProductController::class, 'store']);
$app->router->get('/api/products/{id}', [ProductController::class, 'show']);
$app->router->put('/api/products/{id}', [ProductController::class, 'update']);
$app->router->delete('/api/products/{id}', [ProductController::class, 'delete']);

// With middleware
$app->router->get('/api/users', [UserController::class, 'index'], ['auth', 'throttle:60,1']);
```

### Available Methods

| Method | Route |
|--------|-------|
| `GET` | `$router->get(path, handler, middleware?)` |
| `POST` | `$router->post(path, handler, middleware?)` |
| `PUT` | `$router->put(path, handler, middleware?)` |
| `PATCH` | `$router->patch(path, handler, middleware?)` |
| `DELETE` | `$router->delete(path, handler, middleware?)` |
| `OPTIONS` | `$router->options(path, handler, middleware?)` |
| `GROUP` | `$router->group(prefix, callback)` |

### Route Parameters

```php
$router->get('/api/users/{id}', function (Request $req) {
    $userId = $req->param('id');
});
```

---

## Controllers

Extend `Siro\Core\Controller` for built-in helpers:

```php
use Siro\Core\Controller;
use Siro\Core\Request;
use Siro\Core\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $products = Product::all();
        return $this->success($products, 'Products retrieved');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $product = Product::find($id);
        if (!$product) return $this->error('Not found', 404);
        return $this->success($product, 'Product detail');
    }

    public function store(Request $request): Response
    {
        $data = $this->validate([
            'name' => 'required|min:3|max:255',
            'price' => 'required|numeric|min:0',
        ]);
        $product = Product::create($data);
        return $this->created($product, 'Product created');
    }
}
```

### Controller Helpers

| Method | Description |
|--------|-------------|
| `$this->success($data, $message)` | 200 JSON response |
| `$this->error($message, $code, $errors)` | Error response |
| `$this->created($data, $message)` | 201 response |
| `$this->noContent()` | 204 response |
| `$this->paginated($data, $meta)` | Paginated response |
| `$this->validate($rules)` | Validate request input |
| `$this->input($key)` | Get input value |
| `$this->param($key)` | Get route param |
| `$this->query($key)` | Get query param |
| `$this->user()` | Get authenticated user |

---

## Database / Models

### Query Builder

```php
$users = Database::table('users')
    ->where('status', '=', 1)
    ->where('created_at', '>=', '2025-01-01')
    ->orderBy('id', 'DESC')
    ->limit(20)
    ->get();

$user = Database::table('users')->where('email', '=', $email)->first();

$paginated = Database::table('products')
    ->where('price', '>=', 100)
    ->orderBy('name', 'ASC')
    ->paginate(20, $page);
```

### Model (ORM)

```php
class Product extends Model
{
    protected string $table = 'products';
    protected array $fillable = ['name', 'price', 'stock'];
    protected array $casts = ['price' => 'float', 'stock' => 'int'];
    protected array $hidden = ['internal_code'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

// Usage
$product = Product::find(1);
$products = Product::where('price', '>', 100)->get();
$product = Product::create(['name' => 'New Product', 'price' => 29.99]);
$product->update(['price' => 39.99]);
$product->delete();

// With eager loading
$products = Product::with('category')->get();
```

---

## Authentication

### JWT Auth (Built-in)

```php
// Login returns JWT token pair
POST /api/auth/login
{
    "email": "user@example.com",
    "password": "secret123"
}
// Response:
{
    "access_token": "eyJ...",
    "refresh_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 3600
}

// Protected routes use auth middleware
$router->get('/api/auth/me', [AuthController::class, 'me'], ['auth']);
```

### API Key Auth

```php
$router->get('/api/webhook', [WebhookController::class, 'handle'], ['apikey']);
```

---

## Validation

```php
$validated = $request->validate([
    'email'    => 'required|email|max:255',
    'password' => 'required|min:8|max:255|confirmed',
    'age'      => 'integer|min:18|max:120',
    'role'     => 'in:admin,user,guest',
]);
```

| Rule | Description |
|------|-------------|
| `required` | Field must not be empty |
| `email` | Valid email format |
| `min:N` | Minimum length/value |
| `max:N` | Maximum length/value |
| `numeric` | Must be numeric |
| `integer` | Must be integer |
| `in:a,b,c` | Must be one of |
| `confirmed` | Must match `{field}_confirmation` |

---

## Middleware

Built-in middleware stack:
- **SecurityHeadersMiddleware** — X-Frame-Options, CSP, HSTS, etc.
- **CorsMiddleware** — Cross-Origin Resource Sharing
- **JsonMiddleware** — JSON body parsing + validation
- **AuthMiddleware** — JWT token verification
- **ApiKeyMiddleware** — API key authentication
- **ThrottleMiddleware** — Rate limiting (Redis + file fallback)
- **CsrfMiddleware** — CSRF protection
- **IdempotencyMiddleware** — Idempotency key support

### Custom Middleware

```php
class LogMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): mixed
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;
        Logger::info("{$request->method()} {$request->path()} took {$duration}ms");
        return $response;
    }
}
```

---

## CLI Commands

```bash
php siro make:crud Product          # Full CRUD in 2 seconds
php siro make:controller Product    # Generate controller
php siro make:model Product         # Generate model
php siro make:migration create_products_table
php siro make:service ProductService
php siro make:repository ProductRepository
php siro make:resource ProductResource
php siro make:auth                   # Auth system
php siro make:test ProductTest
php siro migrate                     # Run migrations
php siro route:list                  # List all routes
php siro route:search product       # Search routes
php siro db:show users --schema     # Show table schema
php siro env:check                  # Check environment
php siro doctor                     # System health check
php siro key:generate               # Generate JWT secret
php siro optimize                    # Cache for production
php siro serve                       # Dev server
php siro api:test GET /api/health   # Test endpoint
php siro log:stats                  # Request statistics
php siro why                         # Last request debug
```

---

## Responses

### Success

```json
{
    "success": true,
    "message": "Products retrieved",
    "data": [...],
    "meta": {
        "page": 1,
        "per_page": 20,
        "total": 100,
        "last_page": 5
    }
}
```

### Error

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["Email has already been taken"],
        "password": ["Password must be at least 8 characters"]
    }
}
```

---

## Performance

| Metric | Result |
|--------|--------|
| Route dispatch | ~0.003ms |
| DB SELECT (PK) | ~0.04ms |
| JSON response | < 0.01ms |
| Memory baseline | 4MB |
| Throughput | ~328,000 req/s |
| Runtime deps | 3 (PDO, JSON, mbstring) |

---

## Deployment

```bash
php siro optimize
# Set APP_ENV=production in .env
# Set APP_DEBUG=false

# Nginx config
server {
    listen 80;
    server_name api.example.com;
    root /var/www/my-api/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Why Siro?

| Feature | Siro | Laravel | Slim | Lumen |
|---------|:----:|:-------:|:----:|:-----:|
| Performance | 🚀 328K req/s | ~50K req/s | ~100K req/s | ~80K req/s |
| Memory | 4MB | 12MB | 2MB | 6MB |
| Dependencies | 3 | 50+ | 2 | 5 |
| JWT Auth | Built-in | 3rd party | 3rd party | 3rd party |
| API Keys | Built-in | ❌ | ❌ | ❌ |
| Rate Limiting | Built-in | Built-in | ❌ | ❌ |
| Idempotency | Built-in | ❌ | ❌ | ❌ |
| CRUD Generator | ✅ | ✅ | ❌ | ❌ |
| OpenAPI Generator | ✅ | ❌ | ❌ | ❌ |
| Migrations | ✅ | ✅ | ❌ | ✅ |
| ORM | ✅ | ✅ | ❌ | ❌ |
| Validation | ✅ | ✅ | ❌ | ✅ |
| Queues | ✅ | ✅ | ❌ | ❌ |
| Mail | ✅ | ✅ | ❌ | ❌ |
| Tests | 1,294 | ~5,000 | ~500 | ~300 |
| PHPStan | 0 errors | varies | varies | varies |

---

## Requirements

- PHP 8.2+
- PDO, JSON, mbstring extensions
- SQLite/MySQL/PostgreSQL
- Redis (optional: cache, sessions, rate limiting)

## License

Siro Framework is open-source software licensed under the [MIT license](LICENSE).
