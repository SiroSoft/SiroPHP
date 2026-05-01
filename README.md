# Siro API Framework v0.12.0

**The Fastest PHP Micro-Framework for API Development with Advanced Debugging & CLI Testing**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Packagist](https://img.shields.io/packagist/v/sirosoft/api.svg)](https://packagist.org/packages/sirosoft/api)
[![Downloads](https://img.shields.io/packagist/dt/sirosoft/api.svg)](https://packagist.org/packages/sirosoft/api)

---

## 🚀 Why SiroPHP?

**SiroPHP is designed for API developers who value:**

- ⚡ **Speed** - <1ms request time, zero dependencies
- 🔍 **Debug Fast** - Trace ID system, request replay, CLI testing tool
- 🎯 **Ship Fast** - One-command CRUD scaffolding, `api:test` CLI, auto tests
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
# {"message":"Welcome to Siro API","version":"0.12.0"}
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
php siro make:event UserCreated       # Generate event class
php siro make:lang vi                 # Create language pack
```

### Database
```bash
php siro migrate                      # Run migrations
php siro migrate:rollback             # Rollback migrations
php siro migrate:status               # Check migration status
php siro db:seed                      # Run all seeders
php siro db:seed UserSeeder           # Run specific seeder
```

### Testing & Debugging
```bash
# API Testing (Replace Postman!)
php siro api:test GET /api/users
php siro api:test POST /auth/login email=admin@test.com password=123 --as=admin
php siro api:test GET /users --as=admin              # Auto uses saved token
php siro api:test --history                          # View request history

# Debugging with Trace IDs
php siro log:trace <trace_id>         # View trace details
php siro log:trace --status=500       # Filter by status
php siro log:trace --slow             # Show slow requests
php siro log:replay <trace_id>        # Generate curl command
php siro log:export --format=json     # Export traces

# Advanced Testing Features
php siro api:test GET /api/users --watch              # Watch mode
php siro api:test POST /login --collection-save=myapi # Save to collection
php siro api:test --collection=myapi                  # Run collection
php siro api:test GET /api/users --cors               # CORS validation
php siro api:test POST /webhook --webhook --port=9000 # Webhook listener
```

### CRUD & Test Generation
```bash
php siro make:crud products            # Full CRUD in 30 seconds
php siro make:test ProductApi          # Integration test
php siro make:test ProductService --unit  # Unit test
php siro test                          # Run all tests
```

### Performance & Optimization
```bash
php siro config:cache                 # Cache config
php siro env:check                    # Validate environment
php siro optimize                     # Optimize for production
php siro env:switch staging           # Quick environment switching
php siro slow --limit=20 --min=200    # Analyze slow requests
php siro rate:status                  # Rate limit dashboard
```

### Utilities
```bash
php siro route:list                   # List all routes
php siro serve                        # Start development server
php siro key:generate                 # Generate APP_KEY
php siro doctor                       # Check system health
php siro storage:link                 # Create symlink for uploads
php siro schedule:run                 # Run scheduled tasks
```

### Queue & Mail
```bash
php siro queue:work                   # Process queued jobs
php siro queue:work --daemon          # Run worker continuously
php siro queue:status                 # Show queue status
php siro queue:retry <id>             # Retry failed job
php siro queue:flush                  # Clear failed jobs
```

### Documentation Generation
```bash
php siro make:docs                    # Complete docs with Swagger UI
php siro make:openapi                 # OpenAPI spec only
php siro make:postman                 # Postman collection only
php siro make:docs --flow=auth        # Auth endpoints only
php siro make:docs --tag=User         # User controller only
```

### Static Analysis & Benchmarks
```bash
php phpstan.phar analyse              # PHPStan static analysis
php tests/benchmark.php               # Performance benchmarks
```

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

### Advanced Debugging System
- 🔍 **Trace ID per Request** - Every request gets unique `X-Siro-Trace-Id`
- 🔄 **Request Replay** - `php siro log:replay <id>` generates curl command
- 📤 **Export Traces** - `php siro log:export --format=json|csv`
- 🔎 **Smart Filtering** - Filter by status, method, slow requests
- 📊 **SQL Query Logging** - Capture all queries with bindings and timing
- 🔒 **Credential Sanitization** - Passwords/tokens auto [REDACTED]

### Developer Toolkit
- 🧪 **CLI Testing** - Replace Postman with `api:test` command
- 🔄 **Watch Mode** - Auto re-run tests on code changes
- 📚 **Request Collections** - Batch testing with saved collections
- 🔌 **Webhook Listener** - Receive and display incoming webhooks
- 🌐 **CORS Validation** - Automated 3-step CORS testing
- ⚙️ **Environment Switching** - Quick switch between environments
- 📈 **Slow Request Analysis** - Identify performance bottlenecks
- 🎯 **Rate Limit Dashboard** - Visual rate limit status

### Production-Ready Features
- 🗑️ **Soft Deletes** - Automatic filtering and restoration
- 🏷️ **API Versioning** - Built-in version management
- 🛡️ **Rate Limiting** - Per-route throttling with configurable limits
- 🔐 **CSRF Protection** - Built-in middleware for form protection
- ⚙️ **Config Caching** - Cache environment for faster boot
- 📈 **Slow Query Detection** - Auto-log queries exceeding threshold
- ✅ **Environment Validation** - Pre-deployment checks
- 🗜️ **Auto Gzip Compression** - Reduces bandwidth by 60-80%

### Multi-language Support
- 🌍 **i18n System** - Easy translation management
- 🔍 **Auto Detection** - X-Locale and Accept-Language headers
- 📝 **Pluralization** - Built-in pluralization support

### Event System
- ⚡ **Event Dispatching** - Decoupled event handling
- 🔄 **Model Lifecycle Events** - saving, creating, updating, deleting
- 🚫 **Cancel Operations** - Return false to cancel operations
- 📦 **Event Classes** - Organized event management

### Storage & File Management
- 📁 **File Storage** - Simple put/get/delete operations
- 🔗 **Storage Symlinks** - Easy public access to uploaded files
- 📂 **Directory Management** - Create, list, and manage directories

### Task Scheduling
- ⏰ **Cron-like Scheduling** - Define recurring tasks
- 📅 **Flexible Intervals** - hourly, daily, weekly, custom cron
- 🎯 **Command & Closure Support** - Schedule commands or closures

### Auto Documentation
- 📝 **OpenAPI Generation** - Automatic OpenAPI 3.0.3 spec
- 🎨 **Swagger UI** - Interactive API documentation
- 📮 **Postman Collections** - Auto-generated with pre-request scripts
- 🔍 **Validation Parsing** - Extracts rules from `$request->validate()`
- 🔐 **Security Detection** - Identifies auth middleware automatically
- 🎯 **Smart Filtering** - Filter by tag, method, path, or flow

## 📚 Feature Examples

### API Testing with Auto-Auth
```bash
# Login once, token saved automatically
php siro api:test POST /auth/login email=admin@test.com password=123 --as=admin

# Subsequent requests use saved token
php siro api:test GET /users --as=admin
php siro api:test POST /users name=John email=john@test.com --as=admin
```

### CRUD Scaffolding
```bash
# Generate complete CRUD in 30 seconds
php siro make:crud products

# Creates: Model, Controller, Migration, Routes, Tests
```

### Soft Deletes
```php
use Siro\Core\DB\SoftDeletes;

class Post extends Model { 
    use SoftDeletes; 
}

$post->delete();      // Soft delete
Post::all();          // Auto-filters deleted
$post->restore();     // Restore
$post->forceDelete(); // Permanent delete
```

### API Versioning
```php
$router->version(1, fn($r) => $r->get('/users', [V1\UserCtrl::class, 'index']));
// → GET /api/v1/users

$router->version(2, fn($r) => $r->get('/users', [V2\UserCtrl::class, 'index']));
// → GET /api/v2/users
```

### Event System
```php
use Siro\Core\Event;

// Register listener
Event::on('users.created', function ($user) {
    Log::info('New user: ' . $user->email);
});

// Fire event
Event::emit('users.created', $user);

// Cancel operation
Event::on('users.creating', function ($user): bool {
    if ($user->email === 'banned@example.com') {
        return false; // Cancel creation
    }
    return true;
});
```

### Multi-language
```php
use Siro\Core\Lang;

$message = Lang::get('messages.welcome');  // "Welcome" or "Chào mừng"
$error = Lang::get('validation.required', ['field' => 'Email']);
$apples = Lang::plural('messages.apples', 5);  // "5 apples"
```

### File Storage
```php
use Siro\Core\Storage;

Storage::put('file.txt', 'content');
$content = Storage::get('file.txt');
Storage::delete('file.txt');
$url = Storage::url('file.txt');
```

### Task Scheduling
```php
// In routes/schedule.php
$schedule->command('db:seed UserSeeder')->daily();
$schedule->call(function () {
    // Clean old logs
})->hourly();
$schedule->command('report:weekly')->cron('0 6 * * 1');
```

### Custom Validation
```php
use Siro\Core\Validator;

Validator::extend('phone', function ($value, $field, $input, $param) {
    return preg_match('/^\+?[0-9]{7,15}$/', (string) $value)
        ? true
        : ':field is not a valid phone number';
});

$request->validate(['phone' => 'required|phone']);
```

## 🏗️ Architecture

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

## 📊 Performance

**SiroPHP is 2000-4000x faster than Laravel!**

### Benchmark Results
- ⚡ Cold boot: 0.87ms
- 🚀 GET /: 522K ops/s
- 🔥 Router matching: 893K ops/s
- 💪 Avg throughput: 398K ops/s
- 💾 Memory: 2MB stable, +0KB per request

### Run Benchmarks
```bash
# Start server
php -S localhost:8080 -t public

# Run k6 benchmarks
k6 run benchmark/k6.js

# Run wrk benchmarks
./benchmark/wrk.sh
```

## 📝 Auto Documentation

Generate complete API documentation with Swagger UI:

```bash
php siro make:docs
```

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

**Filtering Options:**
```bash
php siro make:docs --flow=auth        # Auth endpoints only
php siro make:docs --flow=crud        # CRUD operations only
php siro make:docs --tag=User         # Specific controller
php siro make:docs --method=POST      # HTTP method filter
php siro make:docs --path=/api        # Path prefix
```

## 🔍 Advanced Debugging

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

## 📋 Requirements

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

### v0.12.0 - Complete Developer Toolkit
- 🧪 **Test Command** - Run all tests with `php siro test`
- 🔄 **Environment Switching** - Quick switch with `php siro env:switch`
- 📈 **Slow Request Analysis** - Identify bottlenecks with `php siro slow`
- 🔌 **Webhook Listener** - Receive webhooks with `--webhook` flag
- 🌐 **CORS Validation** - Automated CORS testing with `--cors` flag
- 🗑️ **Soft Deletes** - Built-in soft delete support
- 🏷️ **API Versioning** - Version management with `$router->version()`
- 🎯 **Rate Limit Dashboard** - Visual status with `php siro rate:status`

### v0.10.0 - Revolutionary API Testing
- 🔄 **Watch Mode** - Auto re-run tests on code changes
- 📚 **Request Collections** - Batch testing with saved collections
- 📤 **Postman Export** - Export traces to Postman curl commands

### v0.8.8 - API Testing Revolution
- 🧪 **api:test Command** - Replace Postman with CLI testing
- 🔑 **Auto-Auth** - Login once, token saved automatically
- 📜 **Request History** - View and replay previous requests

### v0.8.7 - Storage, Validation & Performance
- 📁 **File Storage** - Simple file management system
- ✅ **Custom Validation** - Extend validation rules
- 🗜️ **Auto Gzip** - Automatic compression for responses

### v0.8.6 - Event System
- ⚡ **Event Dispatching** - Decoupled event handling
- 🔄 **Model Events** - Lifecycle events for CRUD operations
- 🚫 **Cancel Operations** - Return false to cancel

### v0.8.5 - Multi-language Support
- 🌍 **i18n System** - Easy translation management
- 🔍 **Auto Detection** - Locale detection from headers
- 📝 **Pluralization** - Built-in pluralization

### v0.8.4 - Queue & Mail
- 📧 **Mail System** - Send emails with queue support
- ⚙️ **Queue Worker** - Process background jobs
- 🔄 **Job Retry** - Retry failed jobs

### v0.8.3 - Storage & Scheduling
- 🔗 **Storage Links** - Symlink for uploaded files
- ⏰ **Task Scheduling** - Cron-like task scheduler

### v0.8.2 - Auto Documentation
- 📝 **make:docs Command** - Generate complete API docs
- 🎨 **Swagger UI** - Interactive documentation
- 📮 **Postman Collections** - Auto-generated collections

### v0.8.0 - Advanced Debugging System
- 🔍 **Trace ID per Request** - Unique ID for every request
- 🔄 **Request Replay** - Generate exact curl commands
- 📤 **Export Traces** - Export to JSON or CSV
- 📊 **SQL Query Logging** - Capture all queries

---

**Version:** 0.12.0  
**Package:** sirosoft/api  
**Type:** project  
**Released:** May 1, 2026

---

## 👥 Credits

Created and maintained by **SiroSoft Team**

Special thanks to all contributors who help make SiroPHP better.

---

**Happy coding! 🚀**
