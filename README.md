# Siro API Framework v0.8.1

**The Fastest PHP Micro-Framework for API Development with Advanced Debugging & Auto Documentation**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Packagist](https://img.shields.io/packagist/v/sirosoft/api.svg)](https://packagist.org/packages/sirosoft/api)
[![Downloads](https://img.shields.io/packagist/dt/sirosoft/api.svg)](https://packagist.org/packages/sirosoft/api)

---

## 🚀 Why SiroPHP?

**SiroPHP is designed for API developers who value:**

- ⚡ **Speed** - <1ms request time, zero dependencies
- 🔍 **Debug Fast** - Trace ID system, request replay, export capabilities
- 📝 **Auto Docs** - Generate OpenAPI & Postman from your code
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
# {"message":"Welcome to Siro API","version":"0.8.1"}
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
# Generate OpenAPI spec from routes + validation rules
php siro make:openapi
php siro make:openapi --flow=auth       # Only auth endpoints
php siro make:openapi --tag=User        # Only User controller
php siro make:openapi --method=POST     # Only POST requests
php siro make:openapi --path=/api/users # Filter by path

# Generate Postman collection with auto-login
php siro make:postman
php siro make:postman --flow=crud       # Only CRUD endpoints
php siro make:postman --tag=User        # Only User controller
php siro make:postman --method=GET      # Only GET requests

# Output files:
# - openapi.json (OpenAPI 3.0.3 spec)
# - postman_collection.json (Postman v2.1.0 format)
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

### OpenAPI Specification Generator

Automatically generate OpenAPI 3.0.3 specs from your routes and validation rules:

```bash
php siro make:openapi
```

**Features:**
- ✅ **Route Extraction** - Scans all routes from `routes/api.php`
- ✅ **Middleware Detection** - Identifies auth-protected endpoints
- ✅ **Validation Rules Parsing** - Extracts `$request->validate()` rules
- ✅ **Bearer JWT Security** - Adds security scheme for protected routes
- ✅ **Smart Filtering** - Filter by tag, method, path, or flow
- ✅ **Path Parameters** - Auto-detects `{id}` patterns
- ✅ **Query Parameters** - Adds page/per_page for index endpoints
- ✅ **Type Detection** - Converts validation rules to JSON Schema types

**Example Output:**
```json
{
  "paths": {
    "/api/users": {
      "post": {
        "summary": "Store",
        "operationId": "userStore",
        "security": [{"bearerAuth": []}],
        "requestBody": {
          "schema": {
            "type": "object",
            "properties": {
              "name": {"type": "string", "minLength": 3, "maxLength": 120},
              "email": {"type": "string", "format": "email"},
              "password": {"type": "string", "minLength": 6}
            },
            "required": ["name", "email", "password"]
          }
        }
      }
    }
  }
}
```

**Filtering Examples:**
```bash
# Only authentication endpoints
php siro make:openapi --flow=auth

# Only CRUD operations (exclude auth)
php siro make:openapi --flow=crud

# Specific controller
php siro make:openapi --tag=User

# HTTP method filter
php siro make:openapi --method=POST

# Path prefix
php siro make:openapi --path=/api/auth

# Custom output file
php siro make:openapi --output=docs/openapi.json
```

---

### Postman Collection Generator

Generate ready-to-use Postman collections with auto-login:

```bash
php siro make:postman
```

**Features:**
- ✅ **Collection Variables** - `base_url` and `token` pre-configured
- ✅ **Pre-request Script** - Auto-login to fetch bearer token
- ✅ **Body Samples** - Smart examples from validation rules
- ✅ **Path Parameters** - `{id}` converted to Postman `:id` format
- ✅ **Headers** - Content-Type and Accept headers included
- ✅ **Auth Detection** - Bearer token auth configured automatically
- ✅ **Same Filters** - All OpenAPI filters supported

**Auto-Login Script:**
The generated collection includes a pre-request script that:
1. Detects if token is missing
2. Automatically calls `/api/auth/login`
3. Extracts token from response
4. Sets it as collection variable
5. Skips login for auth endpoints themselves

**Example Request:**
```json
{
  "name": "POST /api/users",
  "request": {
    "method": "POST",
    "url": {
      "raw": "{{base_url}}/api/users"
    },
    "header": [
      {"key": "Content-Type", "value": "application/json"},
      {"key": "Accept", "value": "application/json"}
    ],
    "body": {
      "mode": "raw",
      "raw": "{\n  \"name\": \"John Doe\",\n  \"email\": \"user@example.com\",\n  \"password\": \"secret123\"\n}"
    }
  }
}
```

**Usage:**
1. Import `postman_collection.json` into Postman
2. Set `base_url` variable (default: http://localhost:8080)
3. Run any request - token auto-fetched on first call!

**Filtering Examples:**
```bash
# Auth flow only
php siro make:postman --flow=auth

# CRUD operations
php siro make:postman --flow=crud

# Specific controller
php siro make:postman --tag=User

# HTTP method
php siro make:postman --method=GET

# Custom output
php siro make:postman --output=postman/dev-collection.json
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

### v0.8.1 - Auto Documentation System 🌟
- 📝 **OpenAPI Generator** - `php siro make:openapi` generates OpenAPI 3.0.3 spec
- 📮 **Postman Generator** - `php siro make:postman` with auto-login script
- ✅ **Validation Parsing** - Extracts `$request->validate()` rules automatically
- 🔐 **Security Detection** - Identifies auth middleware, adds Bearer JWT scheme
- 🎯 **Smart Filtering** - Filter by tag, method, path, or flow (auth/crud)
- 📊 **Type Inference** - Converts validation rules to JSON Schema types
- 🔗 **Path Parameters** - Auto-detects `{id}` patterns in routes
- 💡 **Body Examples** - Smart defaults from field names and rules

**Generate API docs in 1 second, not 1 hour!**

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

**Version:** 0.8.1  
**Package:** sirosoft/api  
**Type:** project  
**Released:** April 29, 2026

---

## 👥 Credits

Created and maintained by **SiroSoft Team**

Special thanks to all contributors who help make SiroPHP better.

---

**Happy coding! 🚀**
