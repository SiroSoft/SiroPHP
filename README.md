# Siro API Framework v0.8.0

**The Fastest PHP Micro-Framework for API Development with Advanced Debugging**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Packagist](https://img.shields.io/packagist/v/sirosoft/api.svg)](https://packagist.org/packages/sirosoft/api)
[![Downloads](https://img.shields.io/packagist/dt/sirosoft/api.svg)](https://packagist.org/packages/sirosoft/api)

---

## 🚀 Why SiroPHP?

**SiroPHP is designed for API developers who value:**

- ⚡ **Speed** - <1ms request time, zero dependencies
- 🔍 **Debug Fast** - Trace ID system, request replay, export capabilities
- 🎯 **Ship Fast** - One-command auth, auto CRUD scaffolding
- 🛡️ **Secure by Default** - Auto sanitization, rate limiting, CSRF protection
- 💡 **Simple** - Read entire framework in one afternoon

> **"The Laravel alternative that you can read in one afternoon and ship an API in one hour."**

## 🎯 Quick Start

### Install via Composer (Recommended)

```bash
composer create-project sirosoft/api my-app
cd my-app
php siro key:generate
php siro migrate
php siro serve
```

Server starts at: **http://localhost:8080**

### Test the API

```bash
curl http://localhost:8080/
# {"message":"Welcome to Siro API","version":"0.8.0"}
```

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

## 🛠️ CLI Commands

### Code Generation
```bash
php siro make:model User              # Generate model
php siro make:api users               # Generate CRUD API
php siro make:controller UserController
php siro make:migration create_posts_table
php siro make:resource UserResource
php siro make:seeder UserSeeder
php siro make:auth                    # Generate full auth system
```

### Database
```bash
php siro migrate                      # Run migrations
php siro migrate:rollback             # Rollback migrations
php siro migrate:status               # Check migration status
php siro db:seed                      # Run all seeders
php siro db:seed UserSeeder           # Run specific seeder
```

### Debugging (NEW in v0.8.0) 🌟
```bash
php siro log:trace <trace_id>         # View trace details
php siro log:trace --status=500       # Filter by status
php siro log:trace --method=POST      # Filter by method
php siro log:trace --slow             # Show slow requests
php siro log:replay <trace_id>        # Generate curl command
php siro log:export --format=json     # Export traces
```

### Performance
```bash
php siro config:cache                 # Cache config
php siro env:check                    # Validate environment
php siro optimize                     # Optimize for production
```

### Utilities
```bash
php siro route:list                   # List all routes
php siro serve                        # Start development server
php siro key:generate                 # Generate APP_KEY
php siro doctor                       # Check system health
```

### Auto Documentation (NEW in v0.8.1) 🌟
```bash
# Generate complete API documentation with Swagger UI
php siro make:docs
php siro make:docs --flow=auth          # Only auth endpoints
php siro make:docs --tag=User           # Only User controller

# Generate OpenAPI spec only
php siro make:openapi
php siro make:openapi --flow=crud       # CRUD operations only
php siro make:openapi --method=POST     # POST requests only

# Generate Postman collection only
php siro make:postman
php siro make:postman --flow=auth       # Auth flow only
php siro make:postman --tag=User        # User endpoints only

# Output structure:
# docs/openapi/openapi.json      ← OpenAPI 3.0.3 spec
# docs/postman/collection.json   ← Postman v2.1.0 collection
# docs/swagger/index.html        ← Swagger UI source
# public/openapi.json            ← Served at /openapi.json
# public/docs.html               ← Served at /docs.html
```

**Access Documentation:**
- Swagger UI: http://localhost:8080/docs.html
- OpenAPI Spec: http://localhost:8080/openapi.json

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

## 🔍 Advanced Debugging (v0.8.0)

Every request includes a unique trace ID for easy debugging:

```bash
# View trace details
php siro log:trace siro_a1b2c3d4e5f6g7h8

# Filter traces
php siro log:trace --status=500
php siro log:trace --method=POST
php siro log:trace --slow

# Replay request (generates curl command)
php siro log:replay siro_a1b2c3d4e5f6g7h8

# Export traces
php siro log:export --format=json --output=traces.json
php siro log:export --format=csv --output=traces.csv
```

**Debug production issues in 30 seconds, not 30 minutes!**

See core library docs: https://github.com/SiroSoft/siro-core#-advanced-debugging-system-v080

## 📝 Auto Documentation (v0.8.1)

### One-Command Documentation Generation

Generate complete API documentation with Swagger UI in one command:

```bash
php siro make:docs
```

**What it does:**
1. ✅ Generates OpenAPI 3.0.3 spec from your routes
2. ✅ Creates Swagger UI HTML page
3. ✅ Copies files to public/ for serving
4. ✅ Supports all filters (--flow, --tag, --method, --path)

**Output Structure:**
```
docs/
├── openapi/openapi.json      ← Source spec (tracked in git)
├── postman/collection.json   ← Postman collection (tracked)
└── swagger/index.html        ← Swagger UI source (tracked)

public/
├── openapi.json              ← Served at /openapi.json
└── docs.html                 ← Served at /docs.html
```

**Access Your Docs:**
- **Swagger UI:** http://localhost:8080/docs.html
- **OpenAPI Spec:** http://localhost:8080/openapi.json

---

### Individual Generators

#### **OpenAPI Generator**
```bash
php siro make:openapi
php siro make:openapi --flow=auth     # Auth endpoints only
php siro make:openapi --tag=User      # User controller only
php siro make:openapi --method=POST   # POST requests only
php siro make:openapi --path=/api     # Path prefix filter
```

**Features:**
- Extracts validation rules from `$request->validate()`
- Detects auth middleware automatically
- Adds Bearer JWT security scheme
- Generates JSON Schema from validation rules
- Smart type inference (email→string, integer→int)

#### **Postman Generator**
```bash
php siro make:postman
php siro make:postman --flow=crud     # CRUD operations only
php siro make:postman --tag=User      # User endpoints only
php siro make:postman --method=GET    # GET requests only
```

**Features:**
- Collection variables (base_url, token)
- Pre-request script for auto-login
- Body examples from validation rules
- Path parameters auto-mapped
- Same filters as OpenAPI

---

### Filtering Options

All three commands support the same filters:

| Filter | Example | Description |
|--------|---------|-------------|
| `--flow=auth` | `php siro make:docs --flow=auth` | Only authentication endpoints |
| `--flow=crud` | `php siro make:docs --flow=crud` | Only CRUD operations |
| `--tag=User` | `php siro make:docs --tag=User` | Specific controller |
| `--method=POST` | `php siro make:docs --method=POST` | HTTP method filter |
| `--path=/api` | `php siro make:docs --path=/api` | Path prefix |

---

### Workflow Example

```bash
# 1. Make API changes in your controllers

# 2. Regenerate documentation
php siro make:docs

# 3. Test locally
php siro serve
# Visit: http://localhost:8080/docs.html

# 4. Commit documentation source
git add docs/
git commit -m "Update API documentation"

# 5. Deploy (docs are in git, public/ generated on build)
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

## ✨ Key Features

### Core Components
- ⚡ **Fast Router** - Lightweight routing with middleware support
- 🗄️ **Database QueryBuilder** - PDO-based with automatic caching
- 🎯 **Model Layer** - ORM-like with relationships, scopes, soft deletes
- 🔐 **JWT Authentication** - Built-in token generation with refresh tokens
- ✅ **Smart Validation** - Automatic 422 responses with extended rules
- 💾 **Cache System** - File and Redis drivers
- 📦 **Resource Transformation** - Auto-mapping for API responses
- 🔤 **Typed Input Helpers** - Type-safe request data handling

### Advanced Debugging (NEW in v0.8.0) 🌟
- 🔍 **Trace ID per Request** - Every request gets unique `X-Siro-Trace-Id`
- 🔄 **Request Replay** - `php siro log:replay <id>` generates curl command
- 📤 **Export Traces** - `php siro log:export --format=json|csv`
- 🔎 **Smart Filtering** - Filter by status, method, slow requests
- 📊 **SQL Query Logging** - Capture all queries with bindings and timing
- 🔒 **Credential Sanitization** - Passwords/tokens auto [REDACTED]

### Security & Performance
- 🛡️ **Rate Limiting** - Per-route throttling with configurable limits
- 🔐 **CSRF Protection** - Built-in middleware for form protection
- ⚙️ **Config Caching** - Cache environment for faster boot
- 📈 **Slow Query Detection** - Auto-log queries exceeding threshold
- ✅ **Environment Validation** - Pre-deployment checks

## Requirements

- PHP >= 8.2
- PDO extension
- JSON extension
- Mbstring extension

## 📚 Documentation

For detailed documentation:
- **Core Library:** https://github.com/SiroSoft/siro-core
- **Application Skeleton:** https://github.com/SiroSoft/SiroPHP
- **Issues:** https://github.com/SiroSoft/SiroPHP/issues

## 🎯 What's New

### v0.8.0 - Advanced Debugging System 🌟
- 🔍 **Trace ID per Request** - Every response includes `X-Siro-Trace-Id` header
- 🔄 **Request Replay** - `php siro log:replay <id>` generates exact curl command
- 📤 **Export Traces** - `php siro log:export --format=json|csv`
- 🔎 **Smart Filtering** - Filter by status, method, slow requests
- 📊 **SQL Query Logging** - All queries captured with bindings and timing
- 🔒 **Credential Sanitization** - Passwords/tokens automatically [REDACTED]
- 🧹 **Auto Cleanup** - Log rotation (50MB) + retention (30 days)

**Debug production issues in 30 seconds, not 30 minutes!**

### v0.7.10 - Performance Optimization
- ⚙️ **Config Caching** - Cache env + DB config via `php siro config:cache`
- 🩺 **Env Validation** - Comprehensive check with `php siro env:check`
- 🚀 **Optimize Command** - One-command optimization with `php siro optimize`
- 🔍 **Slow Query Logging** - Auto-detect queries > threshold

### v0.7.9 - Auth & Security Hardening
- 🔐 **Complete Auth System** - JWT refresh tokens, email verification, password reset
- 🛡️ **Rate Limiting** - Per-route throttling with `->throttle(maxAttempts, decayMinutes)`
- 🔒 **CSRF Protection** - Built-in CSRF middleware
- ⚡ **Security Headers** - Automatic rate limit headers

### v0.7.8 - Enhanced Developer Experience
- 📊 **Enhanced QueryBuilder** - whereBetween, whereNull, pluck, chunk, exists
- ⚡ **Model Shortcuts** - findOrFail, firstOrCreate, updateOrCreate
- 🎨 **Fluent Response** - Chainable header() and withHeaders() methods
- 🌱 **Database Seeders** - Built-in seeder system
- 📋 **Table Output** - Improved CLI output formatting

---

**Version:** 0.8.0  
**Package:** sirosoft/api  
**Type:** project  
**Released:** April 29, 2026

---

## 👥 Credits

Created and maintained by **SiroSoft Team**

Special thanks to all contributors who help make SiroPHP better.

---

**Happy coding! 🚀**
