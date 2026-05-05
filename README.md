# 🚀 Siro API Framework v0.14.1

**The Fastest PHP Micro-Framework Application Skeleton** — Ship a production-ready API with auth in 5 minutes.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Packagist](https://img.shields.io/badge/packagist-v0.14.1-blue.svg)](https://packagist.org/packages/sirosoft/api)
[![Tests](https://img.shields.io/badge/tests-178%20passing-brightgreen.svg)](tests/)
[![PHPStan](https://img.shields.io/badge/phpstan-level%206-brightgreen.svg)](https://github.com/SiroSoft/siro-core)

---

## 🎯 Why SiroPHP?

| Your Pain | Siro's Solution |
|-----------|----------------|
| 😰 **Onboard in 30 minutes?** | 6 commands from zero → API with auth. Read this README and start coding. |
| 🔄 **Client changes requirements daily?** | `php siro make:crud` scaffolds full CRUD in 2 seconds. Won't break existing code. |
| 💰 **$2/month hosting?** | Pure PHP, **zero dependencies**, ~2MB RAM per request. Runs on any shared host. |
| 🚀 **No DevOps team?** | `php siro deploy` — push to Ubuntu VPS + Nginx + MySQL in one command. |
| 📋 **Client asks "where's the API docs?"** | `php siro make:openapi --with-swagger` — Swagger UI in 1 second. |
| 🔐 **Complex auth?** | Multi-role, JWT access + refresh tokens, OTP, phone login built-in. |
| ⚡ **Worried about upgrades breaking code?** | Strict Semver. Zero breaking changes within same minor. |

> **"The Laravel alternative that runs on $2/month hosting, can be read in one afternoon, and ships an API in one hour."**

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
php siro make:auth                          # Login, register, JWT refresh...
php siro make:crud products                 # Full CRUD: model, migration, routes
php siro migrate                            # Create tables
php siro serve                              # Start server → http://localhost:8080
```

### 3️⃣ Test register + login

```bash
php siro api:test POST /api/auth/register name="Demo" email=demo@test.com password=secret
# {"success":true,"message":"Register successful","data":{"token":"eyJ..."}}

php siro api:test POST /api/auth/login email=demo@test.com password=secret
# {"success":true,"message":"Login successful","data":{"token":"eyJ..."}}
```

### 4️⃣ Test protected CRUD

```bash
php siro api:test GET /api/products            # Public
php siro api:test POST /api/products name=Laptop price=999 --as=admin  # Auto-auth
php siro api:test POST /api/products            # → 401 (no auth)
```

> **That's it.** You now have: registration, login, JWT auth, CRUD API, database, tests, and debugging.  
> Total commands: **6** • Total time: **< 5 minutes**.

---

## ✨ Key Features

### 🏗️ Service & Repository Pattern (v0.14.1)
- 🏗️ **Service Layer** — `php siro make:service Order` generates `app/Services/OrderService.php`
- 🗂️ **Repository Pattern** — `php siro make:repository Product` generates `app/Repositories/ProductRepository.php`
- 🚀 **Full CRUD** — `php siro make:crud invoice` generates Model + Migration + Repository + Service + Controller + Resource + Routes + Test
- 🧠 **Smart Validation** — Auto-detects validation rules based on model name (product → price, sku; order → total; user → email)
- 🔄 **DI Auto-Resolution** — Router auto-resolves constructor dependencies via Reflection (Controller → Service → Repository → Model)

### 🧪 PHPUnit Test Generation (v0.14.1)
- ✅ **`make:test ProductApi`** generates `tests/Feature/ProductApiTest.php` (PHPUnit class)
- ✅ **`make:test CartService --unit`** generates `tests/Unit/CartServiceTest.php`
- ✅ **`make:crud`** generates `tests/Feature/CategoryTest.php` with 4 test methods
- ✅ **`php siro test --filter=CategoryTest`** run single test class
- ✅ **`php siro test --testsuite=Feature`** run feature suite only

### 🛠️ New CLI Tools (v0.13.0+)
- 🏭 **Factory Generator** — `php siro make:factory User` for test data
- 🔍 **Database Inspector** — `php siro db:show users` to view table data & schema
- 📋 **Route Rules Parser** — `php siro route:rules` to extract validation rules
- ⚡ **Live Dev Server** — `php siro live` with auto-reload on changes
- 🚀 **Deployment System** — `php siro deploy` for Git/rsync/custom deploys

### 🔍 Advanced Debugging (v0.8.0)
- 🔍 **Trace ID per Request** — Every request gets unique `X-Siro-Trace-Id`
- 📋 **Request/Response Capture** — Full context including bodies (sanitized)
- 🔄 **Request Replay** — `php siro log:replay <id>` generates curl command
- 📤 **Export Traces** — `php siro log:export --format=json|csv`
- 🔎 **Smart Filtering** — Filter by status, method, slow requests
- 📊 **SQL Query Logging** — Capture all queries with bindings and timing

### 🛡️ Security & Performance
- 🛡️ **Rate Limiting** — Per-route throttling with configurable limits (Redis + file fallback)
- 🔐 **CSRF Protection** — Built-in middleware for form protection
- ⚙️ **Config Caching** — Cache environment for faster boot
- 📈 **Slow Query Detection** — Auto-log queries exceeding threshold
- ✅ **Environment Validation** — Pre-deployment checks via `php siro doctor --prod`

---

## 🛠️ All CLI Commands (57)

### 📦 Code Generation
```bash
php siro make:model User
php siro make:controller UserController
php siro make:migration create_posts_table
php siro make:resource UserResource
php siro make:seeder UserSeeder
php siro make:auth
php siro make:crud products
php siro make:test ProductApi
php siro make:factory User
php siro make:job SendWelcomeEmail
php siro make:mail WelcomeMail
php siro make:event UserCreated
php siro make:lang vi
php siro make:service Order
php siro make:repository Product
php siro make:openapi --with-swagger
php siro make:postman
```

### 🗄️ Database
```bash
php siro migrate
php siro migrate:rollback --step=N
php siro migrate:status
php siro db:seed
php siro db:show users --schema
```

### 🐛 Debugging
```bash
php siro debug:last
php siro log:trace <trace_id>
php siro log:trace --status=500
php siro log:replay <trace_id>
php siro log:export <trace_id> --postman
php siro log:cleanup --days=7
php siro log:slow --limit=10
php siro log:top
php siro log:tail
php siro log:stats
```

### 🧪 Testing
```bash
php siro test
php siro test --filter=CategoryTest
php siro test --testsuite=Feature
php siro api:test GET /api/users
php siro api:test POST /api/auth/login email=admin@test.com password=secret
php siro api:test GET /api/products --as=admin
php siro api:test GET /api/products --loop=100
```

### ⏰ Queue & Schedule
```bash
php siro queue:work
php siro queue:work --daemon
php siro queue:status
php siro queue:retry <id|all>
php siro queue:flush
php siro schedule:run
```

### 🌐 Server & Deploy
```bash
php siro serve --port=8080
php siro live --port=9090
php siro deploy --init
php siro storage:link
```

### ⚙️ System
```bash
php siro key:generate
php siro config:cache
php siro optimize
php siro env:check
php siro env:switch production
php siro doctor
php siro doctor --prod
php siro route:list
php siro route:search user
php siro route:rules
php siro rate:status
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
| `POST` | `/api/auth/logout` | 🚪 Logout, revoke tokens |
| `POST` | `/api/auth/verify-email` | ✅ Verify email address |
| `POST` | `/api/auth/forgot-password` | 🔐 Request password reset |
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

---

## 📁 Project Structure

```
my-app/
├── app/
│   ├── Controllers/       # HTTP controllers
│   ├── Middleware/         # Auth, CORS, JSON, Throttle
│   ├── Models/            # User, Product, Category
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
│   ├── migrations/        # Database migrations
│   ├── seeds/             # Database seeders
│   └── factories/         # Model factories
├── routes/
│   ├── api.php            # API route definitions
│   └── schedule.php       # Scheduled task definitions
├── public/
│   └── index.php          # HTTP entry point
├── tests/
│   ├── unit/              # Unit tests (5)
│   ├── integration/       # Integration tests (7)
│   └── feature/           # Feature tests (11)
└── storage/
    ├── logs/              # Application logs
    ├── cache/             # Cache files
    └── app/               # Uploaded files
```

---

## 📡 Response Format

### ✅ Success (200)
```json
{
  "success": true,
  "message": "Users retrieved",
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

### ❌ Error (4xx/5xx)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 📋 Error Reference

| Status | Condition | Response |
|--------|-----------|----------|
| `401` | Missing/invalid token | `"Unauthorized"` |
| `403` | Inactive account | `"Account is inactive"` |
| `404` | Resource not found | `"{Resource} not found"` |
| `422` | Validation failure | Field-level error messages |
| `429` | Rate limit exceeded | `"Too Many Requests"` |
| `500` | Server error | `"Internal Server Error"` |

---

## 🏗️ Architecture

Siro uses a **two-package architecture**:

```
┌─────────────────────────────────────┐
│  sirosoft/api (SiroPHP)             │
│  Application Skeleton               │
│  ┌───────────────────────────────┐  │
│  │  sirosoft/core (siro-core)    │  │
│  │  Framework Engine             │  │
│  │  Router • DB • JWT • Cache    │  │
│  │  ORM • CLI • Validation       │  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
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

---

## 📚 Learn More

- **📖 Core Framework Docs** → [github.com/SiroSoft/siro-core](https://github.com/SiroSoft/siro-core)
- **🐛 Report Issues** → [github.com/SiroSoft/SiroPHP/issues](https://github.com/SiroSoft/SiroPHP/issues)
- **📦 Packagist** → [packagist.org/packages/sirosoft/api](https://packagist.org/packages/sirosoft/api)

---

**Version:** 0.14.1  
**Package:** sirosoft/api  
**License:** MIT  
**Tests:** 178 ✅ (231 assertions)  

Created and maintained by **SiroSoft Team**
