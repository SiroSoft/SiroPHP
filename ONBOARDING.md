# 🚀 SiroPHP Onboarding — 5 Phút từ Zero đến API

## Yêu cầu
- PHP >= 8.2
- Composer
- PDO extension (MySQL / PostgreSQL / SQLite)

## 1. Install

```bash
composer create-project sirosoft/api my-app
cd my-app
```

## 2. Config

```bash
# Copy env (auto nếu dùng create-project)
cp .env.example .env

# Gen JWT secret
php siro key:generate

# Cấu hình DB trong .env (hoặc dùng SQLite mặc định)
# DB_CONNECTION=sqlite
# DB_DATABASE=storage/app.db
```

## 3. Migrate + Seed

```bash
php siro migrate
php siro db:seed
```

## 4. Start dev server

```bash
php siro serve
# → http://localhost:8080
```

## 5. Test API

```bash
# Health check
php siro t GET /health

# Register user
php siro t POST /api/auth/register --body name=admin --body email=admin@test.com --body password=123456

# Login
php siro t POST /api/auth/login --body email=admin@test.com --body password=123456 --as=admin

# Get users (auto auth)
php siro t GET /api/users --as=admin
```

## 6. CRUD scaffolding

```bash
# Simple CRUD (controller + model)
php siro make:crud products --simple

# Full CRUD (controller + service + repository + migration + test)
php siro make:crud categories

# CRUD với RBAC (chỉ admin mới được POST/PUT/DELETE)
php siro make:crud orders --with-rbac
```

## 7. Generate API docs

```bash
php siro make:openapi --with-swagger
# → http://localhost:8080/docs.html
```

## 8. Debug production

```bash
# Xem request gần nhất
php siro why

# Xem trace cụ thể
php siro log:trace <trace_id>

# Replay request
php siro replay <trace_id>

# Fix + auto-replay
php siro fix
```

## Cấu trúc project

```
my-app/
├── app/
│   ├── Controllers/     # HTTP controllers
│   ├── Middleware/       # Auth, Throttle, CORS, JSON
│   ├── Models/           # Database models
│   ├── Resources/        # API transformers
│   ├── Services/         # Business logic
│   └── Repositories/     # Data access
├── config/
│   └── database.php      # DB config (dùng Config::get('database.host'))
├── database/
│   ├── migrations/       # Schema migrations
│   └── seeds/            # Seeders
├── routes/
│   └── api.php           # Route definitions
├── storage/              # Logs, cache, uploads
├── public/
│   └── index.php         # HTTP entry point
├── siro                  # CLI entry point
└── composer.json
```

## DI Container

```php
// config/app.php (tạo mới)
<?php
return [
    'bindings' => [
        // Interface → Implementation
        // App\Contracts\PaymentInterface::class => App\Services\StripePayment::class,
    ],
    'singletons' => [
        // App\Services\CacheService::class,
    ],
];
```

Container tự động resolve constructor dependencies. Không cần new thủ công:

```php
// Controller - DI tự động
final class ProductController
{
    public function __construct(private ProductService $service) {}
    // $service được inject từ Container
}
```

## RBAC

```php
// routes/api.php — chỉ admin mới được xoá
$router->delete('/users/{id}', [UserController::class, 'delete'])
    ->middleware(['auth:admin']);

// Nhiều role
$router->post('/posts', [PostController::class, 'store'])
    ->middleware(['auth:admin,manager']);
```

## CLI Commands cốt lõi

| Command | Chức năng |
|---------|-----------|
| `php siro start` | Onboarding guide |
| `php siro make:crud <name>` | Full CRUD scaffolding |
| `php siro t GET /path` | Test API endpoint |
| `php siro why` | Debug request gần nhất |
| `php siro fix` | Watch + auto-replay |
| `php siro replay <id>` | Replay request |
| `php siro serve` | Dev server |
| `php siro migrate` | Run migrations |
| `php siro make:openapi` | Generate API docs |

## Hỗ trợ

- GitHub Issues: https://github.com/SiroSoft/SiroPHP/issues
- Core package: https://github.com/SiroSoft/siro-core
- Packagist: https://packagist.org/packages/sirosoft/api
