# Siro API Framework v0.7.8

Minimal, high-performance PHP micro-framework for REST APIs.

## Why Siro?

- ⚡ Faster than full-stack frameworks for API-only workloads
- 🚀 Minimal bootstrap overhead and lightweight request pipeline
- 🎯 Focused on REST API development (no unnecessary layers)
- 📦 Two-package architecture: `sirosoft/core` (library) + `sirosoft/api` (skeleton)

## Quick Start

### Option 1: Install via Composer (Recommended)

```bash
composer create-project sirosoft/api my-app
cd my-app
php siro key:generate
php siro migrate
php siro serve
```

Server will start at: http://localhost:8080

### Option 2: Git Clone

```bash
git clone https://github.com/SiroSoft/SiroPHP.git my-app
cd my-app
composer install
cp .env.example .env
php siro key:generate
php siro migrate
php siro serve
```

## CLI Usage

```bash
php siro migrate                  # Run database migrations
php siro make:api users           # Generate API scaffold
php siro make:controller User     # Create controller
php siro make:migration posts     # Create migration
php siro make:seeder UserSeeder   # Create seeder (NEW in v0.7.8)
php siro db:seed                  # Run seeders (NEW in v0.7.8)
php siro route:list               # List all routes (table format)
php siro serve                    # Start development server
php siro key:generate             # Generate JWT secret
php siro doctor                   # Check environment
```

## Architecture

Siro uses a **two-package architecture**:

- **sirosoft/core** - Framework core library (router, database, cache, JWT, etc.)
  - Install: `composer require sirosoft/core`
  - Repository: https://github.com/SiroSoft/siro-core

- **sirosoft/api** - Application skeleton (this package)
  - Install: `composer create-project sirosoft/api my-app`
  - Repository: https://github.com/SiroSoft/SiroPHP

This architecture allows you to:
- Use `siro/core` in any PHP project
- Build custom applications on top of `siro/api`
- Create your own packages that depend on `siro/core`

## API example

```bash
curl http://localhost:8080/users
```

## Benchmark

Run API server first:

```bash
php -S localhost:8080 -t public
```

### k6

```bash
k6 run benchmark/k6.js
```

Run individual scenarios:

```bash
SCENARIO=root k6 run benchmark/k6.js
SCENARIO=users k6 run benchmark/k6.js
SCENARIO=users_cached k6 run benchmark/k6.js
```

Output includes:

- Requests/sec
- Latency (p95)
- Error rate

Targets:

- API p95 < 20ms
- Cached `/users` p95 < 5ms

### wrk

```bash
./benchmark/wrk.sh
```

Default command used inside script:

```bash
wrk -t4 -c100 -d10s http://localhost:8080/users
```

Output includes:

- Requests/sec
- Latency
- Error rate

## Features

- ⚡ **Fast Router** - Lightweight routing with middleware support
- 🗄️ **Database QueryBuilder** - PDO-based with automatic caching
- 🔐 **JWT Authentication** - Built-in token generation and verification
- 💾 **Cache System** - File and Redis drivers
- 🛠️ **CLI Tools** - Migrations, scaffolding, and more
- ✅ **Validation** - Request validation utilities
- 📦 **Resource Transformation** - API response formatting

## Requirements

- PHP >= 8.2
- PDO extension
- JSON extension
- Mbstring extension

## Documentation

For detailed documentation and examples, visit:
- Core Library: https://github.com/SiroSoft/siro-core
- Main Repository: https://github.com/SiroSoft/SiroPHP

## Testing (v0.7.8)

Siro Core includes 142 comprehensive unit tests covering all core components.

### Running Tests

```bash
cd vendor/sirosft/core
./vendor/bin/phpunit
```

### Test Coverage

- **Validator:** 27 tests - All validation rules
- **Request:** 25 tests - Type-safe input helpers
- **Response:** 17 tests - Response methods & status codes
- **Router:** 18 tests - Route registration & matching
- **Model:** 25 tests - ORM operations & relationships
- **QueryBuilder:** 20 tests - Database query operations
- **Resource:** 10 tests - Data transformation

**Total: 142 unit tests** with PHPUnit infrastructure

### What's New in v0.7.8

- 📊 **Enhanced QueryBuilder** - whereBetween, whereNull, pluck, chunk, exists, inRandomOrder, dump/dd/toSql
- ⚡ **Model Shortcuts** - findOrFail, firstOrCreate, firstOrNew, updateOrCreate
- 🎨 **Fluent Response** - Chainable header() and withHeaders() methods
- 🌱 **Database Seeders** - Built-in seeder system with make:seeder and db:seed commands
- 📋 **Table Output** - Improved CLI output for route:list and migrate:status

## License

MIT License - See LICENSE file for details

## Support

- Issues: https://github.com/SiroSoft/SiroPHP/issues
- Source: https://github.com/SiroSoft/SiroPHP
