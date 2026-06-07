---
title: W OR KF LO W
description: SiroPHP W OR KF LO W reference
sidebar_position: 8
sidebar_label: W OR KF LO W
---

# Developer Workflow

> From installation to production — one continuous flow, no breaks.
>
> **🔁 Replay Debug** · **⚡ 1-command CRUD** · **🔒 OWASP Top 10** · **🧪 19K tests**
>
> **🛠️ From zero to production — CLI only. No PhpStorm, no Postman, no Sequel Ace, no Jenkins.**
> Every task in this workflow is done with `php siro <command>`. Zero third-party tools required.
>
> No other framework — PHP, Node, Go, Rust, Python, Ruby — has all five.

---

## Table of Contents

1. [Install & Run](#1-install--run)
2. [Connect Database](#2-connect-database)
3. [Build Features](#3-build-features)
4. [Manual Testing](#4-manual-testing)
5. [Write Automated Tests](#5-write-automated-tests)
6. [Debug Errors](#6-debug-errors)
7. [Deploy](#7-deploy)

---

> **Everything in this workflow is done from the terminal. Zero GUI tools needed.**
> 
> | Task | Without Siro | With Siro |
> |------|-------------|-----------|
> | Create project | `composer create-project` | `composer create-project sirosoft/api` ✅ |
> | Generate JWT secret | Open random generator website | `php siro key:generate` ✅ |
> | Create database | Open Sequel Ace / phpMyAdmin | `php siro migrate` ✅ |
> | Build CRUD API | Write 6 files manually | `php siro make:crud` ✅ |
> | Test endpoints | Open Postman | `php siro t` ✅ |
> | Debug production | ssh → grep log → guess | `php siro log:replay` ✅ |
> | Deploy | Config Nginx + CI | `php siro deploy` / `docker compose up` ✅ |
> 
> **CLI from A to Z. No third-party tools. No GUI. No context switching.**

---

## 1. Install & Run

### Requirements

```bash
php -v                        # PHP 8.2+
php -m | grep -E "pdo|json|mbstring"  # Required extensions
composer -V                   # Composer
```

### Create Project

```bash
composer create-project sirosoft/api my-app
cd my-app
```

### Configure

```bash
# Auto-generate JWT secret
php siro key:generate

# Validate environment
php siro env:check
```

### Run

```bash
php siro serve
# 👉 http://localhost:8080
```

```bash
curl http://localhost:8080
# {"success":true,"message":"Welcome to Siro API","data":{...}}
```

### Directory Structure

```
my-app/
├── app/
│   ├── Controllers/    # Handle requests
│   ├── Models/         # ORM models
│   ├── Services/       # Business logic
│   ├── Repositories/   # Database access
│   └── Resources/      # JSON transformation
├── config/             # Config files
├── database/
│   └── migrations/     # Database migrations
├── routes/
│   └── api.php         # Route definitions
├── tests/              # Test files
└── storage/            # Logs, cache, sessions
```

---

## 2. Connect Database

### Choose Driver

**SQLite** by default — works immediately, no server needed.

```env
# .env — SQLite (development)
DB_CONNECTION=sqlite
DB_DATABASE=storage/database.sqlite
```

Or **MySQL**:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=my_app
DB_USERNAME=root
DB_PASSWORD=secret
```

### Create Database

```bash
# SQLite — auto-created, nothing to do
# MySQL — create database first:
mysql -u root -p -e "CREATE DATABASE my_app CHARACTER SET utf8mb4"
```

### Run Migrations

```bash
php siro migrate
# ✅ Migration table created successfully.
# ✅ 2026_04_27_110100_create_users_table ........................... 25ms
# ✅ 2026_05_04_000000_create_products_table ........................ 18ms
# ✅ 13 migrations, 0 failures
```

### Verify Connection

```bash
php siro db:show users
# Table users: id, name, email, password, status, created_at, updated_at
```

---

## 3. Build Features

---
> **⚡ Killer Feature #1 — 1-command CRUD**
> Laravel: `php artisan make:model -mcr Product` + manually write Controller, Service, Resource, Test
> **Siro: `php siro make:crud products`** — 6 files, zero config, instantly working.
---

### 3a. Generate Auth (login, register)

```bash
php siro make:auth
php siro migrate
```

**6 endpoints, ready to use:**

```bash
POST /api/auth/register       # Register
POST /api/auth/login          # Login → JWT
POST /api/auth/refresh        # Refresh token
POST /api/auth/logout         # Logout
POST /api/auth/forgot-password
POST /api/auth/reset-password
GET  /api/auth/me             # Profile
```

### 3b. Generate CRUD (one command)

```bash
php siro make:crud products
php siro migrate
```

**1 command generates 6 files:**

| File | Purpose |
|------|---------|
| `app/Models/Product.php` | Model + fillable + casts |
| `app/Controllers/ProductController.php` | CRUD controller |
| `app/Resources/ProductResource.php` | JSON transformer |
| `database/migrations/..._create_products_table.php` | Migration |
| `routes/api.php` | 5 routes auto-injected |
| `tests/Feature/ProductTest.php` | CRUD test |

**API available immediately:**

```bash
GET    /api/products          # List (paginated)
POST   /api/products          # Create
GET    /api/products/{id}     # Detail
PUT    /api/products/{id}     # Update
DELETE /api/products/{id}     # Delete
```

### 3c. Add Service + Repository

For complex business logic:

```bash
php siro make:service ProductService
php siro make:repository ProductRepository
```

### 3d. Add Relationships

```php
// app/Models/Product.php
public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}

public function reviews(): HasMany
{
    return $this->hasMany(Review::class);
}

// Use with eager loading
$products = Product::with('category', 'reviews')->paginate(20);
// 3 queries instead of N+1
```

### 3e. Validation

```php
// In controller
$validated = $request->validate([
    'name' => 'required|string|max:255|unique:products,name',
    'price' => 'required|numeric|min:0',
    'category_id' => 'required|exists:categories,id',
    'description' => 'string|max:10000',
]);
```

### 3f. Middleware (Access Control)

```php
// routes/api.php
$router->resource('products', ProductController::class)
    ->middleware(['auth', 'throttle:60,1']);

// Role-based access
$router->put('/api/users/{id}', [UserController::class, 'update'])
    ->middleware(['auth:admin']);
```

---

## 4. Manual Testing

No Postman needed. Test directly from your terminal.

---
> **⚡ Killer Feature #2 — CLI API Testing (no Postman)**
> Other frameworks: open Postman/Insomnia, enter URL, set headers, type body, copy token...
> **Siro: `php siro t`** — one command, token auto-saved.
---

### 4a. Register + Login

```bash
# Register
php siro t POST /api/auth/register \
    name="John" email="john@test.com" password="secret123"

# Login (token auto-saved with --as)
php siro t POST /api/auth/login \
    email="john@test.com" password="secret123" --as=user
```

### 4b. Test Endpoints

```bash
# Product CRUD (token auto-attached from --as)
php siro t GET /api/products --as=user
php siro t POST /api/products name="Laptop" price=999 --as=user
php siro t GET /api/products/1 --as=user
php siro t DELETE /api/products/1 --as=admin
```

### 4c. Load Test

```bash
php siro t GET /api/products --as=user --loop=100
# Runs 100 requests, shows response times
```

### 4d. List Routes

```bash
php siro route:list
# GET  /api/products         → index
# POST /api/products         → store
# GET  /api/products/{id}    → show
# PUT  /api/products/{id}    → update
```

---

## 5. Write Automated Tests

---
> **⚡ Killer Feature #2 (continued) — Testing without Postman, auto-generated tests**
> Other frameworks: manually write tests, use Postman for API testing, configure separate HTTP client.
> **Siro: `php siro make:crud` auto-generates CRUD tests, `php siro t` for CLI testing, `php siro test` runs everything.**
> From manual testing → automated tests → coverage — all in the terminal, never leave your editor.
---

### 5a. Generate Test

```bash
php siro make:test ProductApi
```

### 5b. Write Test

```php
// tests/Feature/ProductApiTest.php
class ProductApiTest extends TestCase
{
    public function test_can_list_products(): void
    {
        $response = $this->get('/api/products');
        $this->assertSame(200, $response->statusCode());
    }

    public function test_can_create_product(): void
    {
        $headers = $this->authenticate();

        $response = $this->post('/api/products', [
            'name' => 'Test Product',
            'price' => 99.99,
        ], $headers);

        $this->assertSame(201, $response->statusCode());
        $json = $response->json();
        $this->assertSame('Test Product', $json['data']['name']);
    }

    public function test_validation_fails_without_name(): void
    {
        $headers = $this->authenticate();

        $response = $this->post('/api/products', [
            'price' => 99.99,
        ], $headers);

        $this->assertSame(422, $response->statusCode());
    }

    public function test_unauthenticated_user_cannot_create(): void
    {
        $response = $this->post('/api/products', [
            'name' => 'Test',
            'price' => 99.99,
        ]);
        // No token sent → 401
        $this->assertSame(401, $response->statusCode());
    }
}
```

### 5c. Run Tests

```bash
# All tests
php siro test

# Filter by name
php siro test --filter=Product

# With coverage
php siro test --coverage

# Sample output
# PHPUnit 11.5.50
# Tests: 462, Assertions: 783, 0 failures
```

---

## 5d. Export API Docs (Swagger + Postman)

Done coding → export spec immediately. No annotations needed.

```bash
# Export OpenAPI 3.0.3 spec (27 endpoints, auto-detected)
php siro make:openapi --with-swagger
# → docs/openapi.json
# → public/openapi.json    (public URL)
# → public/docs.html       (Swagger UI)

# Export Postman collection (folder structure, auto-login)
php siro make:postman
# → docs/postman/collection.json
# → public/postman_collection.json
```

**Open Swagger UI in browser:**

```
http://localhost:8080/docs.html
```

**Or import into Postman:**

```
http://localhost:8080/postman_collection.json
```

Everything is dynamic — add new APIs, re-export and the spec auto-updates:

| Component | Mechanism |
|-----------|--------|
| **Routes** | Auto-reads from app — add a route and it appears |
| **Request body** | Parsed from `$this->validate([...])` in Controller |
| **Response body** | Parsed from `Resource::toArray()` |
| **Tags/Folders** | From Controller name (`ProductController` → `Products`) |
| **Auth** | Auto-detects middleware `auth` → bearerAuth |
| **Postman auth** | Pre-request script auto-login, auto-attaches token |

---

## 6. Debug Errors

### 6a. Auto Trace (every request)

Every response includes a trace header:

```http
X-Siro-Trace-Id: siro_a1b2c3d4e5
```

### 6b. Find Traces

```bash
# Most recent trace
php siro why

# Trace by ID
php siro log:trace siro_a1b2c3d4e5

# Search without trace ID (only know the broken endpoint)
php siro log:trace --path=/api/orders --status=500 --since=1h

# Search by IP (customer says "my order failed")
php siro log:trace --ip=203.0.113.42 --error="SQL"
```

### 6c. Inspect Trace

```bash
php siro log:trace siro_a1b2c3d4e5
# Output:
#   Route:    POST /api/orders
#   Status:   500
#   Duration: 2.3s
#   Timeline:
#     AuthMiddleware             [3ms]
#     SELECT * FROM products…   [200ms]
#     SELECT * FROM orders…     [1.8s] ← SLOW
#   Exception: PDOException: SQLSTATE[HY000]
#   N+1 Detected: Order::products (50×)
```

---
> **⚡ Killer Feature #3 — Request Replay (THE REAL MOAT)**
> Laravel/Symfony/Rails/FastAPI/Express/Gin: send logs → "let me reproduce it manually"
> **Siro: `php siro log:replay`** — one command to replay the exact production request.
> Search trace → Inspect → Edit → Diff → Verify. All in terminal. No manual reproduction needed.
---

### 6d. Replay Request

No manual reproduction. Replay the exact failed request:

```bash
# Dry-run (safe, default in production)
php siro log:replay siro_a1b2c3d4e5

# Edit body before replay
php siro log:replay siro_a1b2c3d4e5 --edit

# Compare before/after fix
php siro log:replay siro_a1b2c3d4e5 --diff

# Execute for real
php siro log:replay siro_a1b2c3d4e5 --force
```

### 6e. Complete Debug Flow

```
1. Customer reports "order failed"                    ← No trace ID
2. php siro log:trace --path=/api/orders --status=500 --since=1h
3. php siro log:trace siro_a1b2c3                      ← See full context
4. php siro log:replay siro_a1b2c3 --edit              ← Edit + test fix
5. php siro log:replay siro_a1b2c3 --diff              ← Compare results
6. php siro log:replay siro_a1b2c3 --force              ← Verify fix
```

No other framework has this flow.

### 6f. Other Debug Commands

```bash
php siro log:tail              # Real-time log streaming
php siro log:slow              # Slow requests
php siro log:stats             # Request statistics
php siro tinker                # Interactive PHP REPL
```

---

## 7. Deploy

---
> **⚡ Killer Feature #4 — Zero dependency runtime + Security built-in**
> Other frameworks: ~60-200 dependencies (composer install takes 2 minutes, audit finds 10 vulnerabilities).
> **Siro: 0 dependencies.** `composer install` in 5 seconds, `composer audit` = 0 vulnerabilities.
> OWASP Top 10 mitigated from the start — CSP, CORS, CSRF, SQLi (100% prepared statements), XSS.
---

### 7a. Pre-Deploy Checks

```bash
# Comprehensive health check
php siro doctor --prod

# Optimize for production
php siro optimize
# → env cached
# → config cached (HMAC-signed)
# → routes cached
# → autoloader optimized
```

### 7b. Production Checklist

```bash
# 1. Environment
APP_ENV=production
APP_DEBUG=false

# 2. Strong JWT secret (>= 32 chars)
php siro key:generate

# 3. Database migrated
php siro migrate

# 4. Optimize
php siro optimize
```

### 7c. Docker (FrankenPHP)

```bash
# Production (FrankenPHP — multi-worker, HTTP/2, HTTP/3, auto HTTPS)
docker compose up -d

# Verify
curl https://yourdomain.com/health/live
# {"success":true,"data":{"status":"alive"}}
```

### 7d. Manual Deploy

```bash
php siro deploy
# 1. Run tests
# 2. Optimize
# 3. Deploy via Git/rsync
# 4. Restart services
```

---

---

## 🛠️ The CLI Way — A to Z

```
composer create-project sirosoft/api my-app   # 1. Install
cd my-app
php siro key:generate                          # 2. Secret
php siro env:check                             # 3. Validate
php siro make:auth                             # 4. Auth
php siro make:crud Product                     # 5. CRUD
php siro migrate                               # 6. Database
php siro t POST /api/auth/login ... --as=user  # 7. Test
php siro t GET /api/products --as=user         # 8. Verify
php siro test                                  # 9. Automated tests
php siro doctor --prod                         # 10. Pre-deploy
php siro optimize                              # 11. Optimize
docker compose up -d                           # 12. Deploy
```

**12 commands. Zero GUI. Zero third-party. From zero to production.**

```bash
# Bonus: Export API docs for frontend team
php siro make:openapi --with-swagger                # 13. OpenAPI spec
php siro make:postman                                # 14. Postman collection
```

**14 commands. Full API documentation — zero manual writing.**

---

## Comparison: Siro vs Other Frameworks

| Step | Other Frameworks | Siro |
|------|-----------------|------|
| **Install** | `composer install` ~200 deps, 2 min | **0 deps, 5 seconds** |
| **Auth** | Configure JWT for an hour | **`php siro make:auth`** |
| **CRUD** | Write Controller + Model + Migration + Test manually | **`php siro make:crud`** |
| **API testing** | Open Postman, config headers, type body | **`php siro t`** — one command |
| **Debug production** | Send logs → reproduce manually | **`php siro log:replay`** — exact replay |
| **Security** | Configure each layer yourself | **OWASP Top 10 mitigated by default** |
| **Boot time** | 50-80ms (Laravel) | **~1ms** |
| **Deploy config** | Nginx + PHP-FPM + manual config | **`docker compose up -d`** FrankenPHP |
