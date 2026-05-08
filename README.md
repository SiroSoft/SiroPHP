# 🚀 Siro API Framework v0.16.6

**The Fastest PHP Micro-Framework Application Skeleton** — Ship a production-ready API with auth in 5 minutes. Built-in DI Container, Config Repository, RBAC support.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Packagist](https://img.shields.io/badge/packagist-v0.16.6-blue.svg)](https://packagist.org/packages/sirosoft/api)
[![Tests](https://img.shields.io/badge/tests-215%20passing-brightgreen.svg)](tests/)
[![PHPStan](https://img.shields.io/badge/phpstan-level%206-brightgreen.svg)](https://github.com/SiroSoft/siro-core)
[![PostgreSQL](https://img.shields.io/badge/postgresql-ready-blue.svg)](https://www.postgresql.org/)
[![Security](https://img.shields.io/badge/security-log%20sanitized-brightgreen)](https://github.com/SiroSoft/siro-core)

---

## 🎯 Why SiroPHP?

| Your Pain | Siro's Solution |
|-----------|----------------|
| 😰 **Onboard in 30 minutes?** | 6 commands from zero → API with auth. Read this README and start coding. |
| 🔄 **Client changes requirements daily?** | `php siro make:crud` scaffolds full CRUD in 2 seconds. Won't break existing code. |
| 💰 **$2/month hosting?** | Pure PHP, **zero dependencies**, ~2MB RAM per request. Runs on any shared host. |
| 🚀 **No DevOps team?** | `php siro deploy` — push to Ubuntu VPS + Nginx + MySQL in one command. |
| 📋 **Client asks "where's the API docs?"** | `php siro make:openapi --with-swagger` — Swagger UI in 1 second. |
| 🔐 **Complex auth?** | JWT access + refresh tokens, email verification, forgot/reset password, token versioning. |
| 🐘 **Which database?** | MySQL / PostgreSQL / SQLite — one codebase, all three, zero changes. |
| 🔧 **Server down for deploy?** | `php siro down` — 503 maintenance mode with IP allowlist. `php siro up` — back live. |

> **"The Laravel alternative that runs on $2/month hosting, can be read in one afternoon, and ships an API in one hour."**

---

## 🧩 New in v0.16.0

### DI Container (`Siro\Core\Container`)
- **Bind interfaces**: `$container->bind(AuthInterface::class, AuthService::class)`
- **Singleton**: `$container->singleton(Cache::class, RedisCache::class)`
- **Auto-resolution**: Constructor dependencies resolved via reflection
- Router tự động dùng Container để resolve controller dependencies

### Config Repository (`Siro\Core\Config`)
- Load tất cả `config/*.php` files tự động
- **Dot-notation**: `Config::get('database.host', 'default')`
- **Hỗ trợ cache**: `php siro config:cache`

### RBAC — Role-Based Access Control
- `AuthMiddleware::handle($request, $next, ...$roles)` — check role ngay trong middleware
- Dùng: `->middleware(['auth:admin'])` — chỉ admin mới access được
- `make:crud --with-rbac` — sinh routes có auth:admin cho mutations

---

## ⚡ Zero to API with Auth in 5 Minutes

### 1️⃣ Install

```bash
composer create-project sirosoft/api my-app
cd my-app
php siro key:generate
```

### 2️⃣ Generate auth system + first CRUD

```bash
php siro make:auth                          # Login, register, JWT refresh + forgot/reset password
php siro make:crud products                 # Full CRUD: model, migration, controller, routes, tests
php siro migrate                            # Create tables (MySQL/PgSQL/SQLite auto-detected)
php siro serve                              # Start dev server → http://localhost:8080
```

### 3️⃣ Test register + login

```bash
php siro api:test POST /api/auth/register name="Demo" email=demo@test.com password=secret
# {"success":true,"message":"Register successful","data":{"token":"eyJ...","refresh_token":"eyJ..."}}

php siro api:test POST /api/auth/login email=demo@test.com password=secret
# {"success":true,"message":"Login successful","data":{"token":"eyJ..."}}
```

### 4️⃣ Test protected CRUD

```bash
php siro api:test GET /api/products            # Public list
php siro api:test POST /api/products name=Laptop price=999 --as=admin  # With JWT auth
php siro api:test POST /api/products            # → 401 (no auth required for write)
```

### 5️⃣ Debug when something goes wrong

```bash
php siro debug:last                             # Show last request: headers, body, SQL queries
php siro log:replay a1b2c3d4 --force            # Replay any past request (modifiable)
php siro api:test GET /api/products --loop=100  # Load test
```

### 6️⃣ Health check (for load balancers)

```bash
curl http://localhost:8080/health
# {"success":true,"message":"OK","data":{"status":"healthy","database":"connected","version":"0.15.0"}}
```

> **That's it.** You now have: registration, login, JWT auth, CRUD API, database, tests, debugging, and health check.  
> Total commands: **7** • Total time: **< 5 minutes**

---

## ✨ Key Features

### 🏗️ Schema Builder (v0.15.0)
Write migration ONCE, run on ANY database — no if/else branches:

```php
use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

Schema::create('orders', function (Blueprint $t) {
    $t->id();
    $t->string('customer_name');
    $t->decimal('total', 10, 2)->default(0);
    $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $t->timestamps();
    $t->softDeletes();
});
```

This single migration works on **MySQL** (AUTO_INCREMENT, InnoDB), **PostgreSQL** (BIGSERIAL, no UNSIGNED), and **SQLite** (INTEGER AUTOINCREMENT, TEXT dates) — automatically.

**Available methods:** `id()`, `string()`, `text()`, `integer()`, `smallint()`, `bigint()`, `decimal()`, `float()`, `boolean()`, `date()`, `datetime()`, `timestamp()`, `json()`, `timestamps()`, `softDeletes()`, `rememberToken()`, `unique()`, `index()`, `foreign()`, `default()`, `nullable()`, `useCurrent()`

**Schema Introspection:** `Schema::hasTable('users')`, `hasColumn('users', 'email')`, `getColumnListing('users')`, `hasDatabase('my_app')`

### 🔗 Multi-Database Connections (v0.15.0)
Connect to multiple databases from one application:

```php
// Default connection (from config/database.php)
$users = DB::table('users')->get();

// Named connection for analytics/replica
$analytics = DB::table('events')->connection('analytics')->get();

// Raw PDO access
$pdo = Database::connection('analytics');
$pdo->query('SELECT COUNT(*) FROM page_views');

// List all connections
$names = Database::connections(); // ['default', 'analytics']
```

### 🔐 Encryption (v0.15.0)
AES-256-CBC encryption with HMAC integrity:

```php
use Siro\Core\Encrypter;

$encrypted = Encrypter::encrypt($creditCardNumber);
// Store $encrypted in database

$decrypted = Encrypter::decrypt($encrypted);
// "4111-1111-1111-1111"
```

Uses `APP_KEY` or `JWT_SECRET` from `.env` automatically. Tampered data throws `RuntimeException`.

### 🌐 HTTP Client (v0.15.0)
Call external APIs with zero dependencies:

```php
use Siro\Core\Http;

// GET request
$response = Http::get('https://api.github.com/users/octocat');
$data = $response->json();  // ['login' => 'octocat', ...]

// POST with JSON body
$response = Http::post('https://api.example.com/orders', [
    'product' => 'Laptop',
    'quantity' => 2,
]);

echo $response->status();   // 201
echo $response->body();     // Raw response string
echo $response->ok() ? 'Success' : 'Failed';
echo $response->header('content-type');
```

### 🔧 Maintenance Mode (v0.15.0)
Enable/disable maintenance mode without touching the server:

```bash
php siro down --message="Upgrading database..."    # Returns 503 to all requests
php siro down --allow=192.168.1.100               # Allow specific IP through
php siro up                                        # Restore live mode
```

The application automatically returns `503 Service Unavailable` with `Retry-After` header.

### 🐘 PostgreSQL Production Support (v0.15.0)

| Feature | MySQL | PostgreSQL | SQLite |
|---------|-------|------------|--------|
| Auto-detect port | 3306 | 5432 | 0 |
| Primary key | `AUTO_INCREMENT` | `BIGSERIAL` | `AUTOINCREMENT` |
| Identifier quoting | `` `backtick` `` | `"double quote"` | `` `backtick` `` |
| Boolean | `TINYINT(1)` | `BOOLEAN` | `TINYINT(1)` |
| Index/Unique | Inline in CREATE | `CREATE INDEX` separate | `CREATE INDEX` separate |
| `RETURNING id` on INSERT | Not supported | ✅ Yes | Not supported |
| Random ordering | `RAND()` | `RANDOM()` | `RANDOM()` |

### 🔒 Production Security (v0.15.0)
- 🔒 **Log Sanitization** — Passwords, tokens, credit cards, OTPs auto `[REDACTED]` in traces
- 🛡️ **Replay Lock** — `--dry-run` only in production, need `--force --env=local` for write operations
- 📝 **Audit Trail** — Every replay/dry-run/diff logged to `storage/logs/replay-audit.log`
- 🧹 **Log Protection** — `.htaccess` auto-generated, Nginx check in `doctor`, retention & rotation
- 🚫 **Log Injection Prevention** — Newlines escaped in all log entries
- 🔐 **OpenAPI Production Lock** — Disabled by default in production (`SIRO_OPENAPI_ENABLED=true` to enable)

### 🏗️ Service & Repository Pattern (v0.14.1)
- 🏗️ **Service Layer** — `php siro make:service Order` generates `app/Services/OrderService.php`
- 🗂️ **Repository Pattern** — `php siro make:repository Product` generates `app/Repositories/ProductRepository.php`
- 🚀 **Full CRUD** — `php siro make:crud invoice` generates Model + Migration + Repository + Service + Controller + Resource + Routes + Test
- 🔄 **DI Auto-Resolution** — Router auto-resolves constructor dependencies via Reflection

### 🧪 PHPUnit Test Generation (v0.14.1)
- ✅ **`make:test ProductApi`** generates `tests/Feature/ProductApiTest.php`
- ✅ **`make:crud`** generates `tests/Feature/CategoryTest.php` with 4 test methods
- ✅ **Fluent Test Helpers (v0.15.0)** — `$this->get('/')->assertStatus(200)->assertJson(['key'=>'val'])`
- ✅ **Database Assertions** — `$this->assertDatabaseHas('users', ['email'=>'test@test.com'])`

### 🔍 Advanced Debugging (v0.8.0)
- 🔍 **Trace ID per Request** — Every response includes `X-Siro-Trace-Id` header
- 📋 **Request/Response Capture** — Full context including bodies (sanitized)
- 🔄 **Request Replay** — `php siro log:replay <id>` replays any past request
- 📤 **Export Traces** — `php siro log:export --format=json|csv|postman`
- 📊 **SQL Query Logging** — All queries captured with bindings and timing

### 🛡️ Security
- 🛡️ **Rate Limiting** — Per-route throttling with Redis + file fallback
- 🔐 **CSRF Protection** — Built-in middleware
- ✅ **Mass Assignment Protection** — Secure default blocks unauthorized field updates
- 🔒 **Credential Sanitization** — Passwords/tokens auto `[REDACTED]` in logs

---

## 🛠️ CLI (59 commands)

### 🎯 Core Workflow (90% daily use)
```bash
php siro make:crud products           # Full CRUD in 2 seconds
php siro serve                        # Start dev server
php siro api:test GET /api/products   # Test any endpoint (alias: t)
php siro why                          # Why did the last request fail?
php siro fix                          # Watch code changes & auto-replay
php siro replay                       # Replay any past request
php siro traces                       # Browse recent request traces
```

### 🔧 Daily Dev Tools
```bash
php siro make:controller User    php siro make:model User
php siro make:migration create   php siro make:test ProductApi
php siro make:service Order      php siro make:repository Product
php siro make:auth               php siro make:seeder UserSeeder
php siro migrate                 php siro db:seed
php siro test                    php siro route:list
```

### 📦 Advanced / Infra
```bash
php siro make:job SendEmail      php siro make:mail WelcomeMail
php siro make:event UserCreated  php siro make:lang vi
php siro make:factory User       php siro make:openapi --with-swagger
php siro make:postman            php siro make:resource UserResource
php siro queue:work              php siro queue:status
php siro schedule:run            php siro deploy --init
php siro optimize                php siro config:cache
php siro down --message="Upgrading..."  php siro up
php siro log:trace <id>          php siro log:slow --limit=10
php siro log:replay <id> --edit  php siro log:replay <id> --diff
php siro log:export <id> --postman
```

### ⚙️ System / Rare
```bash
php siro key:generate            php siro doctor --prod
php siro env:check               php siro env:switch production
php siro route:search user       php siro route:rules
php siro rate:status             php siro db:show users --schema
php siro migrate:status          php siro migrate:rollback --step=N
php siro storage:link            php siro live --port=9090
php siro log:cleanup --days=7    php siro log:tail
php siro log:stats               php siro log:top
```

---

## 📡 API Endpoints

Run `php siro route:list` to see all registered routes.

### 🔐 Authentication

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/auth/register` | 👤 Register new user |
| `POST` | `/api/auth/login` | 🔑 Login, returns JWT + refresh token |
| `POST` | `/api/auth/refresh` | 🔄 Refresh access token |
| `POST` | `/api/auth/logout` | 🚪 Logout, revoke all tokens |
| `POST` | `/api/auth/verify-email` | ✅ Verify email address |
| `POST` | `/api/auth/forgot-password` | 🔐 Request password reset link |
| `POST` | `/api/auth/reset-password` | 🔑 Reset password with token |
| `GET` | `/api/auth/me` | 👁️ Get authenticated user profile |

### 📦 CRUD Resources

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/users` | 📋 List users (paginated, cached) |
| `GET` | `/api/users/{id}` | 👤 Get user by ID (cached) |
| `POST` | `/api/users` | ➕ Create user |
| `PUT` | `/api/users/{id}` | ✏️ Update user |
| `DELETE` | `/api/users/{id}` | 🗑️ Delete user |
| `GET` | `/api/products` | 📋 List products |
| `GET` | `/api/categories` | 📋 List categories |

### 💚 Health

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/health` | 💚 Health check (DB status, version, time) |

---

## 📁 Project Structure

```
my-app/
├── app/
│   ├── Controllers/       # HTTP controllers (Auth, User, Product, Category)
│   ├── Middleware/         # Auth (JWT), CORS, JSON, Throttle middleware
│   ├── Models/            # User, Product, Category (extends Siro\Core\Model)
│   ├── Services/          # Business logic layer
│   ├── Repositories/      # Data access layer
│   ├── Resources/         # API response transformers
│   ├── Jobs/              # Queueable jobs
│   ├── Events/            # Event classes
│   ├── Mails/             # Email templates
│   └── Crons/             # Scheduled tasks
├── config/
│   └── database.php       # Database configuration
├── database/
│   ├── migrations/        # Database migrations (Schema Builder)
│   ├── seeds/             # Database seeders
│   └── factories/         # Model factories
├── routes/
│   ├── api.php            # API route definitions
│   └── schedule.php       # Scheduled task definitions
├── public/
│   └── index.php          # HTTP entry point
├── tests/
│   ├── unit/              # 5 unit tests
│   ├── integration/       # 7 integration tests
│   └── feature/           # 11 feature tests
└── storage/
    ├── logs/              # Application logs (daily rotation, 30 days retention)
    ├── cache/             # Cache files
    └── app/               # Uploaded files
```

---

## 📡 Response Format

All API responses follow a consistent JSON structure.

### ✅ Success (200)
```json
{
  "success": true,
  "message": "Users retrieved",
  "data": [{"id": 1, "name": "John", "email": "john@test.com"}],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

### ❌ Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field must be at least 6 characters."]
  }
}
```

### 📋 Error Reference

| Status | When | Body |
|--------|------|------|
| `401` | Missing/invalid/expired token | `{"message": "Unauthorized", "errors": {"token": ["Invalid or expired token"]}}` |
| `403` | Inactive/disabled account | `{"message": "Account is inactive"}` |
| `404` | Resource not found | `{"message": "User not found"}` |
| `422` | Validation failure | `{"message": "Validation failed", "errors": {"field": ["error"]}}` |
| `429` | Rate limit exceeded | `{"message": "Too Many Requests", "errors": {"throttle": ["Rate limit exceeded."]}}` |
| `500` | Server error (debug) | `{"message": "Internal Server Error", "trace": "...", "file": "...", "line": 42}` |
| `503` | Maintenance mode | `{"message": "Upgrading database...", "data": null}` (+ `Retry-After` header) |

---

## 🧪 Testing

### Unit Tests (178 tests, 231 assertions)

Run the full test suite:
```bash
php vendor/bin/phpunit                          # All 178 tests
php vendor/bin/phpunit --testsuite=Unit          # 26 unit tests
php vendor/bin/phpunit --testsuite=Integration   # 42 integration tests
php vendor/bin/phpunit --testsuite=Feature       # 110 feature tests
```

### HTTP Test Helpers (v0.15.0)

```php
class ProductApiTest extends TestCase
{
    public function test_list_products(): void
    {
        $this->get('/api/products')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_create_product_requires_auth(): void
    {
        $this->post('/api/products', ['name' => 'Laptop'])
            ->assertUnauthorized();
    }

    public function test_database_state(): void
    {
        $this->post('/api/auth/register', [
            'name' => 'John',
            'email' => 'john@test.com',
            'password' => 'secret123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'john@test.com']);
    }
}
```

### Available Assertions
- `assertStatus(int)` — Assert HTTP status code
- `assertOk()` — Assert 200
- `assertCreated()` — Assert 201
- `assertUnauthorized()` — Assert 401
- `assertNotFound()` — Assert 404
- `assertValidationError()` — Assert 422
- `assertJson(array)` — Assert keys in JSON response
- `assertJsonPath('key.nested', $value)` — Assert nested JSON path
- `assertHeader(name, value)` — Assert response header

### Database Assertions (v0.15.0)
- `assertDatabaseHas('users', ['email' => '...'])` — Assert row exists
- `assertDatabaseMissing('users', ['email' => '...'])` — Assert row doesn't exist

---

## 🏗️ Architecture

```
┌──────────────────────────────────────────────────┐
│              sirosoft/api (SiroPHP)               │
│              Application Skeleton                 │
│  ┌────────────────────────────────────────────┐  │
│  │         sirosoft/core (siro-core)          │  │
│  │         Framework Engine (136 tests)        │  │
│  │                                            │  │
│  │  Router • Middleware • Database • Cache     │  │
│  │  JWT Auth • Validation • Queue • Mail     │  │
│  │  Schema Builder • ORM • CLI (57 cmds)      │  │
│  │  Encrypter • Http Client • Logger          │  │
│  └────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────┘
```

| Package | Type | Install |
|---------|------|---------|
| `sirosoft/core` | ⚙️ Framework Core | `composer require sirosoft/core` |
| `sirosoft/api` | 🚀 Application Skeleton | `composer create-project sirosoft/api my-app` |

---

## 📋 Requirements

- ✅ PHP >= 8.2
- ✅ PDO extension (`pdo_mysql` / `pdo_pgsql` / `pdo_sqlite`)
- ✅ JSON extension
- ✅ Mbstring extension
- ✅ OpenSSL extension (for Encrypter)
- ✅ cURL extension (for HTTP Client)

## 📋 Supported Databases

| Database | Driver | Status |
|----------|--------|--------|
| MySQL 5.7+ | `mysql` | ✅ Production ready |
| MariaDB 10.2+ | `mysql` | ✅ Production ready |
| PostgreSQL 12+ | `pgsql` | ✅ Production ready (v0.15.0) |
| SQLite 3.x | `sqlite` | ✅ Development/Testing |

---

## 📋 Changelog

- **v0.16.0** — DI Container (`Siro\Core\Container`) with autowiring, singleton, interface binding. Config Repository (`Siro\Core\Config`) with dot-notation, caching. 4 middleware moved to core (Auth, Throttle, Cors, Json). RBAC: `auth:admin` role checks in middleware, `make:crud --with-rbac`. New tests: 26 (Container, Config, Middleware). Fixed OrderController, migration for orders table. PHPStan level 6 — 0 errors. **197 tests → 223 tests**
- **v0.15.0** — Schema Builder (driver-agnostic migrations), Multi-DB connections, AES-256 Encryption, HTTP Client, Maintenance mode (`php siro down/up`), Foreign Key constraints, Health endpoint (`GET /health`), Test assertion helpers (`assertStatus`, `assertJson`, `assertDatabaseHas`), PostgreSQL production support, **Production Security** (log sanitization, replay lock, audit trail, log protection, log injection prevention, OpenAPI production lock), **CLI UX Overhaul** (core workflow, `php siro start` onboarding, `t` alias, layered help), Str helper, Hash facade, Collection class, FormRequest, Signed URLs, Task withoutOverlapping, Fake implementations (Queue::fake, Mail::fake, Storage::fake), Queue Dashboard, Fix command (watch + auto-replay), Trace list command, OpenAPI spec generation (dynamic, 35 endpoints, 34 schemas)
- **v0.14.1** — Service & Repository pattern, PHPUnit test generation, `make:service`, `make:repository`, `make:crud` with full layers
- **v0.14.0** — `debug:last`, `log:top`, `route:search`, `doctor --prod`, `api:test --loop`
- **v0.13.0** — Factory generator, `db:show`, `route:rules`, live reload, deploy system
- **v0.12.0** — `make:crud` scaffolding, `make:test`, benchmarks, `env:switch`
- **v0.11.0** — Service & Repository, eager loading, PHP 8.4 support
- **v0.10.0** — Rate limiter, CSRF, config caching, optimize
- **v0.9.0** — Queue, mail, events, scheduler, multi-language
- **v0.8.0** — Debugging system (trace ID, replay, export), Swagger UI, Postman
- **v0.7.0** — Initial release

---

## 📚 Learn More

- **📖 Core Framework Docs** → [github.com/SiroSoft/siro-core](https://github.com/SiroSoft/siro-core)
- **🐛 Report Issues** → [github.com/SiroSoft/SiroPHP/issues](https://github.com/SiroSoft/SiroPHP/issues)
- **📦 Packagist** → [packagist.org/packages/sirosoft/api](https://packagist.org/packages/sirosoft/api)

---

**Version:** 0.16.6  
**Package:** sirosoft/api  
**License:** MIT  
**Tests:** 215 ✅ (336 assertions) — PHPUnit  
**Core:** sirosoft/core v0.16.2 (243 tests, 359 assertions)  
**PHPStan:** Level 6 ✅ — 0 errors  
**CLI:** 59 commands — layered UX (core → daily → advanced → system)  

Created and maintained by **SiroSoft Team**
