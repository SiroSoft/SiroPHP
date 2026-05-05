# Siro API Framework v0.14.1

**The Fastest PHP Micro-Framework for API Development with Advanced Debugging & CLI Testing**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-178%20passing-brightgreen.svg)](tests/)

---

## Zero to API with Auth in 5 Minutes

```bash
composer create-project sirosoft/api my-app
cd my-app
php siro key:generate
php siro make:auth
php siro make:crud products
php siro migrate
php siro serve
```

That's it. You now have: registration, login, JWT auth, CRUD API, database, tests, and debugging.

---

## CLI Commands (57 commands)

### Code Generation
```
php siro make:model User
php siro make:controller UserController
php siro make:migration create_posts_table
php siro make:resource UserResource
php siro make:seeder UserSeeder
php siro make:auth
php siro make:crud products
php siro make:test ProductApi
php siro make:job SendWelcomeEmail
php siro make:mail WelcomeMail
php siro make:event UserCreated
php siro make:lang en messages
php siro make:factory UserFactory
php siro make:service UserService
php siro make:repository UserRepository
php siro make:openapi --with-swagger
php siro make:postman
```

### Database
```
php siro migrate
php siro migrate:rollback --step=N
php siro migrate:status
php siro db:seed
php siro db:show users --schema
```

### Debugging
```
php siro debug:last
php siro log:trace <trace_id>
php siro log:replay <trace_id>
php siro log:export <trace_id> --postman
php siro log:cleanup --days=7
php siro log:slow --limit=10
php siro log:top
php siro log:tail
php siro log:stats
```

### Queue & Schedule
```
php siro queue:work
php siro queue:work --daemon
php siro queue:status
php siro queue:retry <id|all>
php siro queue:flush
php siro schedule:run
```

### Server & Deploy
```
php siro serve --port=8080
php siro live --port=9090
php siro deploy --init
php siro storage:link
```

### System & Config
```
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

### Testing
```
php siro test
php siro api:test GET /api/users
php siro api:test POST /api/auth/login email=test@test.com password=secret
php siro api:test GET /api/products --loop=50
```

---

## Architecture

Siro uses a **two-package architecture**:

- **[sirosoft/core](https://github.com/SiroSoft/siro-core)** - Framework core library (router, database, cache, JWT, ORM, console, 57 CLI commands)
- **[sirosoft/api](https://github.com/SiroSoft/SiroPHP)** - Application skeleton (this package)

```
composer require sirosoft/core           # Use core in any PHP project
composer create-project sirosoft/api app  # Start new project
```

---

## Requirements

- PHP >= 8.2
- Extensions: `pdo`, `json`, `mbstring`
- Database: MySQL / MariaDB / PostgreSQL / SQLite

---

## Response Format

All API responses follow a consistent JSON structure:

### Success (200)
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

### Error (4xx/5xx)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Error Reference

### 401 Unauthorized - No Token
```json
{ "success": false, "message": "Unauthorized", "errors": { "token": ["Missing bearer token"] } }
```

### 401 Unauthorized - Invalid/Expired Token
```json
{ "success": false, "message": "Unauthorized", "errors": { "token": ["Invalid or expired token"] } }
```

### 403 Forbidden - Account Inactive
```json
{ "success": false, "message": "Invalid credentials", "errors": {} }
```

### 404 Not Found
```json
{ "success": false, "message": "User not found" }
```

### 422 Validation Error
```json
{ "success": false, "message": "Validation failed", "errors": { "name": ["The name field is required."] } }
```

### 429 Rate Limited
```json
{ "success": false, "message": "Too Many Requests", "errors": { "throttle": ["Rate limit exceeded."] } }
```

---

## API Testing

```bash
# Basic test
php siro api:test GET /api/users

# Test with body
php siro api:test POST /api/auth/login email=admin@test.com password=secret

# Authenticated request
php siro api:test POST /api/products name=Laptop price=999 --as=admin

# Load test
php siro api:test GET /api/products --loop=100

# Replay past request
php siro log:replay a1b2c3d4
```

---

## Debugging

Every request gets a unique trace ID. Debug with:

```bash
php siro debug:last              # Last request: headers, body, SQL
php siro log:trace <id>          # Full trace details
php siro log:replay <id>         # Replay any past request
php siro log:slow --limit=20     # Find slowest endpoints
php siro log:tail                # Real-time log viewer
```

---

## Changelog

- **v0.14.x** - PHPUnit test generation, `make:test`, service/repository layers, `debug:last`, `log:top`, `route:search`, `doctor --prod`, `api:test --loop`
- **v0.13.x** - Factory generator, `db:show`, `route:rules`, live reload, deploy system, PHPStan Level 6
- **v0.12.x** - `make:crud` scaffolding, benchmarks, watch mode, collections, `env:switch`
- **v0.11.x** - Service & Repository pattern, eager loading, PHP 8.4 support
- **v0.10.x** - Rate limiter, CSRF, config caching, optimize command
- **v0.9.x** - Queue system, mail, events, scheduler, multi-language
- **v0.8.x** - Debugging system (trace ID, replay, export), OpenAPI/Swagger, Postman generator
- **v0.7.x** - Initial release: router, models, JWT auth, validation, migrations

---

**Version:** 0.14.1
**Package:** sirosoft/api
**License:** MIT

Created and maintained by **SiroSoft Team**
