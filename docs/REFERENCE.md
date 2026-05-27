---
title: R EF ER EN CE
description: SiroPHP R EF ER EN CE reference
sidebar_position: 5
sidebar_label: R EF ER EN CE
---

# SiroPHP Reference Documentation

> Detailed API reference for all Siro components.

---

## 🏗️ Architecture

| Document | Description |
|----------|-------------|
| [Architecture Overview](ARCHITECTURE.md) | Design decisions, request lifecycle, directory structure |
| [Security Architecture](SECURITY.md) | OWASP Top 10 mitigation, threat model, best practices |
| [Performance Guide](PERFORMANCE.md) | Benchmarking, optimization, tuning |
| [Benchmark Results](BENCHMARK.md) | Performance benchmarks and comparisons |

---

## 📚 API Reference

### Core

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Container** | [api/Container.md](api/Container.md) | DI Container, autowiring, bindings, contextual DI |
| **Request** | [api/Request.md](api/Request.md) | Input, headers, files, validation, authentication |
| **Response** | [api/Response.md](api/Response.md) | Success/error responses, pagination, raw responses |
| **Router** | [api/Router.md](api/Router.md) | Routes, groups, middleware, attributes, caching |
| **Middleware** | [api/Middleware.md](api/Middleware.md) | Built-in middleware, custom middleware, pipeline |

### Database & ORM

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Model** | [api/Model.md](api/Model.md) | ORM, CRUD, relationships, scopes, soft deletes |
| **Query Builder** | [guides/DATABASE.md](guides/DATABASE.md) | SELECT, JOIN, WHERE, aggregates, pagination |
| **Migrations** | [guides/DATABASE.md](guides/DATABASE.md) | Schema builder, create/alter/drop tables |
| **Relations** | [guides/DATABASE.md](guides/DATABASE.md) | HasOne, HasMany, BelongsTo, BelongsToMany |

### Auth & Security

| Component | Reference | Description |
|-----------|-----------|-------------|
| **JWT Auth** | [guides/AUTHENTICATION.md](guides/AUTHENTICATION.md) | Access/refresh tokens, key rotation, RBAC |
| **CORS** | [api/Middleware.md](api/Middleware.md) | Cross-Origin Resource Sharing |
| **CSRF** | [api/Middleware.md](api/Middleware.md) | CSRF token protection |
| **CSP** | [api/Middleware.md](api/Middleware.md) | Content-Security-Policy headers |
| **Rate Limiting** | [api/Middleware.md](api/Middleware.md) | Throttle middleware, Redis + file |
| **Encryption** | [api/Encryption.md](api/Encryption.md) | AES-256-CBC, HKDF, Encrypt-then-MAC |
| **Validation** | [api/Validation.md](api/Validation.md) | 15+ rules, custom rules, FormRequest |

### Services

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Cache** | [guides/CACHING.md](guides/CACHING.md) | File/Redis, query caching, prefix-based invalidation |
| **Logger** | [api/Logger.md](api/Logger.md) | Log levels, sanitization, channels, rotation |
| **Events** | [api/Events.md](api/Events.md) | Pub/sub, wildcards, model lifecycle hooks, listeners |
| **Queue** | [api/Queue.md](api/Queue.md) | DB-based jobs, retry, timeout, priority, failed jobs |
| **Mail** | [api/Mail.md](api/Mail.md) | SMTP, sendmail, async queuing, attachments, mail classes |
| **Storage** | [api/Storage.md](api/Storage.md) | Local, S3, path traversal protection, file uploads |
| **Session** | [api/Session.md](api/Session.md) | File/Redis sessions, flash data, CSRF tokens, regeneration |

### Data & Utilities

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Resource** | [api/Resource.md](api/Resource.md) | JSON transformation, field hiding, XSS protection |
| **Collection** | [api/Collection.md](api/Collection.md) | Fluent array wrapper with 30+ helper methods |
| **Helpers** | [api/Helpers.md](api/Helpers.md) | `dd()`, `dump()` — debug helpers |
| **Pagination** | [api/Pagination.md](api/Pagination.md) | Built-in pagination with meta response |

### Observers & Hooks

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Observers** | [api/Observers.md](api/Observers.md) | Model lifecycle hooks (10 events) |
| **SoftDeletes** | [api/SoftDeletes.md](api/SoftDeletes.md) | Soft delete trait with restore/force |

### Scheduler & CLI

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Schedule** | [api/Schedule.md](api/Schedule.md) | Cron-like task scheduling |
| **Console / Custom Commands** | [api/Console.md](api/Console.md) | Register custom CLI commands |
| **CLI Reference** | [api/CLI.md](api/CLI.md) | Full 72-command reference with examples |

### Debug & Testing

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Debug** | [api/Debug.md](api/Debug.md) | Trace system, request replay, log management, health checks |
| **Testing** | [api/Testing.md](api/Testing.md) | HTTP helpers, authentication, factories, test structure |
| **FormRequest** | [api/FormRequest.md](api/FormRequest.md) | Encapsulated validation + authorization |

### Utilities

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Str** | [api/Str.md](api/Str.md) | String manipulation: slug, case, truncate, pluralize |
| **URL** | [api/Url.md](api/Url.md) | Signed URLs with HMAC validation |
| **Http** | [api/Http.md](api/Http.md) | Zero-dependency HTTP client (cURL) |
| **Config** | [api/Config.md](api/Config.md) | Config loader, HMAC-signed caching |
| **Lang** | [api/Lang.md](api/Lang.md) | i18n translation with locale fallback |

### Extras

| Component | Reference | Description |
|-----------|-----------|-------------|
| **Metrics** | [api/Metrics.md](api/Metrics.md) | Prometheus metrics, custom counters/histograms |
| **UploadedFile** | [api/UploadedFile.md](api/UploadedFile.md) | File handling, type checks, secure storage |
| **Hash** | [api/Hash.md](api/Hash.md) | Bcrypt password hashing with configurable cost |

---

## 🛠️ CLI Commands

### Code Generation

| Command | Description |
|---------|-------------|
| `php siro make:crud <name>` | Full CRUD (model, controller, migration, routes, tests) |
| `php siro make:auth` | Auth system (JWT, register, login, forgot/reset) |
| `php siro make:controller <name>` | Controller class |
| `php siro make:model <name>` | Model class |
| `php siro make:migration <name>` | Migration file |
| `php siro make:service <name>` | Service class |
| `php siro make:repository <name>` | Repository class |
| `php siro make:middleware <name>` | Middleware class |
| `php siro make:resource <name>` | API resource transformer |
| `php siro make:request <name>` | FormRequest class |
| `php siro make:test <name>` | PHPUnit test |
| `php siro make:job <name>` | Queue job |
| `php siro make:mail <name>` | Mail class |
| `php siro make:event <name>` | Event class |
| `php siro make:listener <name>` | Event listener |
| `php siro make:seeder <name>` | Database seeder |
| `php siro make:factory <name>` | Model factory |
| `php siro make:openapi` | OpenAPI/Swagger spec |
| `php siro make:postman` | Postman collection |

### Database

| Command | Description |
|---------|-------------|
| `php siro migrate` | Run pending migrations |
| `php siro migrate:rollback` | Rollback last batch |
| `php siro migrate:status` | Migration status |
| `php siro db:seed` | Run seeders |
| `php siro db:show <table>` | Table schema |

### Debugging

| Command | Description |
|---------|-------------|
| `php siro why` | Show why last request failed |
| `php siro log:tail` | Tail log files |
| `php siro log:trace <id>` | View request trace |
| `php siro log:replay <id>` | Replay request (--edit, --diff, --force) |
| `php siro log:slow` | Slow requests |
| `php siro log:stats` | Log statistics |
| `php siro log:search` | Search traces by IP/path/error/status |
| `php siro tinker` | Interactive PHP REPL |

### Testing

| Command | Description |
|---------|-------------|
| `php siro test` | Run all tests |
| `php siro t` | Quick API test from CLI |
| `php siro benchmark` | Performance benchmarks |

### Cache & Config

| Command | Description |
|---------|-------------|
| `php siro config:cache` | Cache config (HMAC-signed) |
| `php siro config:clear` | Clear config cache |
| `php siro env:cache` | Cache environment |
| `php siro env:check` | Validate environment |
| `php siro optimize` | Full production optimization |

### System

| Command | Description |
|---------|-------------|
| `php siro key:generate` | Generate JWT secret |
| `php siro serve` | Start dev server |
| `php siro doctor` | System health check |
| `php siro route:list` | List all routes |
| `php siro up` | Disable maintenance mode |
| `php siro down` | Enable maintenance mode |
| `php siro deploy` | Deploy application |
| `php siro queue:work` | Process queue |
| `php siro schedule:run` | Run scheduled tasks |

---

## ⚙️ Configuration Reference

### `.env` File

```env
# Application
APP_NAME="Siro API"
APP_ENV=local              # local, production, testing
APP_DEBUG=false
APP_URL=http://localhost

# JWT
JWT_SECRET=your-32-char-secret
JWT_TTL=3600               # Access token TTL (seconds)
JWT_REFRESH_TTL=604800     # Refresh token TTL (seconds)
JWT_ALGORITHM=HS256        # HS256 or RS256

# Database
DB_CONNECTION=sqlite       # sqlite, mysql, pgsql
DB_DATABASE=storage/db.sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=file          # file, redis
CACHE_TTL=3600
CACHE_PREFIX=siro:

# Redis (optional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3000
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With

# Mail
MAIL_DRIVER=log            # log, smtp, sendmail
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@localhost
MAIL_FROM_NAME="Siro API"
MAIL_SSL_VERIFY=true

# Rate Limiting
THROTTLE_FALLBACK=fail_closed  # fail_closed or disabled

# Logging
LOG_LEVEL=debug
LOG_RETENTION_DAYS=30
LOG_MAX_SIZE_MB=1024
```

---

## 🧪 Error Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized (invalid/expired token) |
| 403 | Forbidden (insufficient role) |
| 404 | Not Found |
| 422 | Validation Error |
| 429 | Too Many Requests (rate limited) |
| 500 | Internal Server Error |

Response format:

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {},
    "meta": {}
}
```

Error format:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["Email is required"],
        "password": ["Password must be at least 8 characters"]
    }
}
```
```
