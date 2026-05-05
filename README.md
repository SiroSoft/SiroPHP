# Siro API Framework v0.14.1

**Application Skeleton for the Siro Core Framework**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-178%20passing-brightgreen.svg)](tests/)

---

## What is SiroPHP?

SiroPHP is the **application skeleton** for the [Siro Core Framework](https://github.com/SiroSoft/siro-core). It provides a ready-to-use project structure with authentication, CRUD scaffolding, API testing, debugging tools, and 57 CLI commands -- all running on pure PHP with zero external dependencies.

```bash
composer create-project sirosoft/api my-app
cd my-app
php siro serve
# Your API is live at http://localhost:8080
```

---

## Quick Start

### 1. Create Project

```bash
composer create-project sirosoft/api my-app
cd my-app
php siro key:generate
```

### 2. Generate Auth + First CRUD

```bash
php siro make:auth              # Login, register, JWT refresh tokens
php siro make:crud products     # Full CRUD: model, migration, controller, routes, tests
php siro migrate                # Create database tables
php siro serve                  # Start dev server: http://localhost:8080
```

### 3. Test Your API

```bash
php siro api:test POST /api/auth/register name="Demo" email=demo@test.com password=secret
php siro api:test POST /api/auth/login email=demo@test.com password=secret
php siro api:test GET /api/products
php siro api:test POST /api/products name=Laptop price=999 --as=admin
```

### 4. Debug When Something Goes Wrong

```bash
php siro debug:last              # See full request: headers, body, SQL
php siro log:replay a1b2c3d4     # Replay any past request
php siro log:trace a1b2c3d4      # View trace details
```

---

## Architecture

Siro uses a **two-package architecture**:

| Package | Type | Repository |
|---------|------|------------|
| `sirosoft/core` | Framework Core | [github.com/SiroSoft/siro-core](https://github.com/SiroSoft/siro-core) |
| `sirosoft/api` | Application Skeleton | [github.com/SiroSoft/SiroPHP](https://github.com/SiroSoft/SiroPHP) (this package) |

The core library provides the engine (router, database, cache, JWT, ORM, console, 57 CLI commands). The skeleton provides the project structure, controllers, models, middleware, routes, and tests.

---

## Project Structure

```
my-app/
  app/
    Controllers/       # HTTP controllers (Auth, User, Product, Category, etc.)
    Middleware/        # Auth, CORS, JSON, Throttle middleware
    Models/            # User, Product, Category models
    Services/          # Business logic layer
    Repositories/      # Data access layer
    Resources/         # API response transformers
    Jobs/              # Queueable jobs
    Events/            # Event classes
    Mails/             # Email templates
    Crons/             # Scheduled tasks
  config/
    database.php       # Database configuration
  database/
    migrations/        # Database migrations
    seeds/             # Database seeders
    factories/         # Model factories
  routes/
    api.php            # API route definitions
    schedule.php       # Scheduled task definitions
  public/
    index.php          # HTTP entry point
  tests/
    unit/              # Unit tests
    integration/       # Integration tests
    feature/           # Feature tests
  storage/
    logs/              # Application logs
    cache/             # Cache files
    app/               # Uploaded files
```

---

## CLI Commands (57 total)

The CLI is provided by the `sirosoft/core` package. Run `php siro list` to see all commands.

### Code Generation

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

### Database

```bash
php siro migrate
php siro migrate:rollback --step=N
php siro migrate:status
php siro db:seed
php siro db:show users --schema
```

### Debugging

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

### Testing

```bash
php siro test
php siro test --filter=CategoryTest
php siro test --testsuite=Feature
php siro api:test GET /api/users
php siro api:test POST /api/auth/login email=admin@test.com password=secret
php siro api:test GET /api/products --as=admin
php siro api:test GET /api/products --loop=100
```

### Queue & Schedule

```bash
php siro queue:work
php siro queue:work --daemon
php siro queue:status
php siro queue:retry <id|all>
php siro queue:flush
php siro schedule:run
```

### Server & Deploy

```bash
php siro serve --port=8080
php siro live --port=9090
php siro deploy --init
php siro storage:link
```

### System

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

## API Endpoints

The skeleton comes with pre-built endpoints. Run `php siro route:list` to see all routes.

### Authentication

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/auth/register` | Register new user |
| POST | `/api/auth/login` | Login, returns JWT + refresh token |
| POST | `/api/auth/refresh` | Refresh access token |
| POST | `/api/auth/logout` | Logout, revoke tokens |
| POST | `/api/auth/verify-email` | Verify email address |
| POST | `/api/auth/forgot-password` | Request password reset |
| POST | `/api/auth/reset-password` | Reset password with token |
| GET | `/api/auth/me` | Get authenticated user |

### CRUD Resources

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/users` | List users (paginated, cached) |
| GET | `/api/users/{id}` | Get user by ID (cached) |
| POST | `/api/users` | Create user |
| PUT | `/api/users/{id}` | Update user |
| DELETE | `/api/users/{id}` | Delete user |
| GET | `/api/products` | List products |
| GET | `/api/categories` | List categories |
| GET | `/api/tag` | List tags |

---

## Response Format

All API responses follow a consistent JSON structure.

### Success

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

### Error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Error Reference

| Status | Condition | Message |
|--------|-----------|---------|
| 401 | Missing/invalid token | `Unauthorized` |
| 403 | Inactive account | `Account is inactive` |
| 404 | Resource not found | `{Resource} not found` |
| 422 | Validation failure | `Validation failed` + field errors |
| 429 | Rate limit exceeded | `Too Many Requests` |
| 500 | Server error | `Internal Server Error` |

---

## Requirements

- PHP >= 8.2
- Extensions: `pdo`, `json`, `mbstring`
- Database: MySQL / MariaDB / PostgreSQL / SQLite

---

## Learn More

- **Core Framework Docs**: [github.com/SiroSoft/siro-core](https://github.com/SiroSoft/siro-core)
- **Report Issues**: [github.com/SiroSoft/SiroPHP/issues](https://github.com/SiroSoft/SiroPHP/issues)

---

**Version:** 0.14.1
**Package:** sirosoft/api
**License:** MIT

Created and maintained by **SiroSoft Team**
