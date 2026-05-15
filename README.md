<div align="center">
  <h1>⚡ Siro API Framework v0.26.2</h1>
  <p><strong>The Fastest, Lightest, Most Secure PHP Micro-Framework</strong></p>
  <p>Zero dependencies • Sub-millisecond boot • JWT built-in • 70 CLI commands • OWASP Top 10 mitigated</p>
</div>

<div align="center">

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP 8.2+](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-19037%20total-brightgreen.svg)](tests/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-brightgreen.svg)](https://phpstan.org)
[![Psalm](https://img.shields.io/badge/Psalm-Level%201-brightgreen.svg)](https://psalm.dev)
[![Security](https://img.shields.io/badge/security-OWASP%20Top%2010%20Mitigated-brightgreen)](docs/SECURITY.md)
[![Mutation](https://img.shields.io/badge/mutation-MSI%20≥80%25-brightgreen)](https://infection.github.io)
[![SBOM](https://img.shields.io/badge/sbom-CycloneDX-blue)](https://cyclonedx.org)
[![Packagist](https://img.shields.io/packagist/v/sirosoft/api?color=blue)](https://packagist.org/packages/sirosoft/api)
[![Downloads](https://img.shields.io/packagist/dt/sirosoft/api?color=blue)](https://packagist.org/packages/sirosoft/api)

</div>

---

```bash
# Zero → Production API with Auth in 5 minutes
composer create-project sirosoft/api my-api && cd my-api && php siro serve
#                                                                     ^
#                                  http://localhost:8080 with JWT auth + CRUD
```

## The Siro Flow — Integrated Terminal-Native Workflow

**For local development and small-team API workflows, you rarely need to leave the terminal.**

```bash
# ── BUILD ──────────────────────────────────────────────
composer create-project sirosoft/api my-api
cd my-api
php siro key:generate
php siro make:crud Product       # Controller + Service + Repository + Model + Migration + Test
php siro make:auth               # Auth: register/login/refresh/forgot/reset
php siro migrate
php siro serve                   # Start at :8080

# ── TEST ───────────────────────────────────────────────
php siro t POST /api/auth/login --body='{"email":"test@test.com","password":"123456"}'
php siro t GET /api/products
php siro t POST /api/orders --body='{"product_id":1,"quantity":5}'

# ── DEBUG ──────────────────────────────────────────────
php siro why                      # Why did production fail? (5 seconds)
php siro replay siro_a1b2c3       # Replay exact failed request
php siro replay siro_a1b2c3 --edit # Edit body → test fix
php siro tinker                   # Interactive PHP playground

# ── MONITOR ────────────────────────────────────────────
php siro log:tail                 # Local log streaming
php siro log:stats                # Request stats
php siro doctor                   # System health check
curl localhost:8080/health        # HTTP health check
curl localhost:8080/metrics       # Prometheus endpoint

# ── DOCUMENT ───────────────────────────────────────────
php siro make:openapi --with-swagger
php siro route:list
```

**Everything above is built-in — zero packages to install for these workflows.**  
(For production-scale monitoring, Siro integrates with standard tools: Prometheus, Grafana, Datadog.)

---

## Why Siro?

| You struggle with | Siro solves it |
|------------------|----------------|
| **Laravel/Symfony too heavy** (~60-100 dependencies) | **Zero** runtime dependencies. Just PHP + PDO |
| **Slow boot** (50-80ms per request) | **~1ms** cold boot. 50-80x faster |
| **JWT auth takes hours to setup** | **Built-in**. Algorithm pinning, key rotation, JTI blacklist |
| **N+1 queries kill performance** | **Auto-detected**. `php siro why` shows exact N+1 warnings with fix |
| **Manual CRUD boilerplate** | **1 command**: `make:crud Product` generates Controller + Service + Repository + Model + Migration + Test |
| **Security left to developers** | **OWASP Top 10** mitigated from the start: CSP, CORS, CSRF, Rate Limit, SQLi, XSS |
| **Dependency vulnerabilities** | **Zero transitive dependencies**. Composer audit = 0 issues |

---

## 6 Commands → Full API with Auth

```bash
# 1. Generate JWT secret (32-byte random)
php siro key:generate

# 2. Auth system: register, login, refresh, logout, forgot/reset password
php siro make:auth

# 3. Full CRUD for your resources
php siro make:crud Product
php siro make:crud Order
php siro make:crud Category

# 4. Create database tables
php siro migrate

# 5. Start production-grade server (FrankenPHP multi-worker)
php siro frankenphp:serve --docker
#    Or dev server:
php siro serve --port=8080

# API is ONLINE with full auth + CRUD ✅
```

---

## What You Get

### Security — Hardened by Default

| Protection | How Siro Handles It |
|------------|-------------------|
| JWT Algorithm Pinning | Never trusts the token's `alg` header |
| JWT Key Rotation | Version-tracked secrets, seamless rotation |
| JTI Blacklist | Revoke individual tokens on demand |
| CSP Middleware | Content-Security-Policy with strict-dynamic |
| CORS | Configurable origins, credentials support |
| CSRF | Session-based + double-submit cookie for SPAs |
| Rate Limiter | Redis primary + file fallback |
| Audit Log | SIEM-ready `security.log` output |
| SQL Injection | 100% prepared statements everywhere |
| XSS | `htmlspecialchars` + CSP headers |

### Performance — Unreal for PHP

```
Benchmark                          Result
─────────────────────────────────────────────────────
  Static route dispatch           0.002ms  (488K ops/sec)
  Dynamic route dispatch          0.009ms
  Middleware pipeline (10 layers) 0.012ms  (negligible)
  Cold boot (no cache)            ~1ms     (Linux + OPcache)
  1000 routes registered          1.2ms
  Memory per request              ~2KB     (no leak)
  JSON serialize (1000 items)     1.8ms
  SQLite query (500 rows)         0.5ms
  Full app lifecycle              ~0.4ms   (2,300 req/sec)
```

### 70 CLI Commands

```
  make:*       23 commands     make:auth, make:crud, make:controller, make:model...
  db:*          5 commands     migrate, rollback, seed, show
  log:*         9 commands     tail, trace, replay, stats, slow, export, cleanup, top
  queue:*       4 commands     work, retry, flush, status
  cache:*       3 commands     config:cache, config:clear, env:cache
  server:*      4 commands     serve, frankenphp:serve, live, deploy
  debug:*       3 commands     debug:last, debug:health, tinker
  system:*     20 commands     key:generate, benchmark, route:list, test, doctor...
```

Every command supports `--help`. Typo-tolerant with Levenshtein suggestion.

---

## Architecture

```
Request → Router → [Middleware Pipeline] → Controller → Service → Repository → Model → DB
                                                               ↕
                                                            Resource → JSON Response
```

```
📁 app/
├── Controllers/     # Handle requests, return responses
├── Services/        # Business logic layer
├── Repositories/    # Database access layer
├── Models/          # ORM models
├── Resources/       # JSON transformation
├── Middleware/       # Auth, JSON, SecurityHeaders
└── Exceptions/      # Custom exception classes

📁 config/           # app.php, database.php, jwt.php, cors.php, cache.php, mail.php
📁 routes/           # api.php
📁 database/         # migrations/
📁 storage/          # logs/, cache/, sessions/
📁 public/           # index.php (entry point)
```

---

## API Endpoints (After `make:auth`)

```http
### Authentication (public)
POST /api/auth/register          # {name, email, password}
POST /api/auth/login             # {email, password} → {token, refresh_token}
POST /api/auth/refresh           # {refresh_token} → {token, refresh_token}
POST /api/auth/forgot-password   # {email}
POST /api/auth/reset-password    # {token, password}
POST /api/auth/logout            # [Bearer] → Revoke token

### User (authenticated)
GET  /api/auth/me                # [Bearer] → Profile
GET  /api/users                  # [Bearer] → List (paginated)
POST /api/users                  # [Bearer] → Create
GET  /api/users/{id}             # [Bearer] → Detail
PUT  /api/users/{id}             # [Bearer] → Update
DELETE /api/users/{id}           # [Bearer] → Delete
```

Response format:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": { "id": 1, "name": "Demo", "email": "demo@test.com" }
  },
  "meta": { "page": 1, "per_page": 20, "total": 50, "last_page": 3 }
}
```

Error format:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { "email": ["Email has already been taken"] }
}
```

---

## Feature Breakdown

| Feature | Details |
|---------|---------|
| **JWT Auth** | Access + Refresh tokens, algorithm pinning (HS256/RS256), key rotation, JTI blacklist, audience validation |
| **CRUD Generator** | `php siro make:crud Product` → Controller + Service + Repository + Model + Resource + Migration + Test |
| **Query Builder** | SELECT, JOIN (INNER/LEFT/RIGHT/CROSS), WHERE, GROUP BY, HAVING, subqueries, pagination, aggregates, `whereHas`, row locking (FOR UPDATE/SHARE) |
| **ORM** | HasOne, HasMany, BelongsTo, BelongsToMany, eager loading, soft deletes |
| **Migrations** | Create, rollback, status. Supports MySQL, PostgreSQL, SQLite |
| **Validation** | 15+ rules: required, email, unique, exists, min, max, confirmed, in, regex, file, image, date, url. Custom rules + messages |
| **Cache** | File + Redis drivers, auto-prefix, query/route/config caching |
| **Rate Limiting** | Per-route configurable, Redis primary + file fallback |
| **Middleware** | Auth, CORS, CSP, CSRF, ETag, Version, Metrics, Audit, Throttle, Idempotency, SecurityHeaders, JSON |
| **File Storage** | Local filesystem, S3-compatible (AWS Signature V4), upload validation (MIME + size) |
| **Queue** | DB-based jobs, exponential backoff, timeout, priority, failed job retry |
| **Mail** | Sendmail + SMTP (STARTTLS, AUTH LOGIN), async queuing, HTML + attachments |
| **Events** | Pub/sub, wildcards, one-time listeners. Model events: creating, created, saving, saved, deleting, deleted |
| **Debug** | `X-Siro-Trace-Id`, request replay (`log:replay`), slow query detection, log sanitization, `debug:last`, `siro tinker` REPL |
| **Observers** | Model lifecycle hooks via `Model::observe()` — saving, creating, updating, deleting, force deleting |
| **Gzip Files** | Automatic compression for text-based file downloads (text, JSON, XML, SVG, fonts) |
| **API Versioning** | Header-based via `Accept: application/vnd.siro.v2+json`, route overrides per version |
| **Prometheus** | `/metrics` endpoint, auto-track request count, duration histogram, status codes |
| **CLI** | 70 commands with help, aliases, Levenshtein suggestion on typos |
| **Encryption** | AES-256-CBC, HKDF key separation, Encrypt-then-MAC, `hash_equals` timing-safe |

---

## Production Deployment

```bash
# FrankenPHP (multi-worker, HTTP/2, HTTP/3, auto HTTPS)
docker compose up frankenphp

# Or build yourself
docker build -f Dockerfile.frankenphp -t my-api .
docker run -p 80:80 -p 443:443 -v .env:/app/.env my-api
```

---

## Documentation

| Module | Link | Description |
|--------|------|-------------|
| **Database** | [docs/DATABASE.md](https://github.com/SiroSoft/siro-core/blob/main/docs/DATABASE.md) | QueryBuilder, Models, Migrations, Relations |
| **Cache** | [docs/CACHE.md](https://github.com/SiroSoft/siro-core/blob/main/docs/CACHE.md) | File/Redis, query caching |
| **Logger** | [docs/LOGGER.md](https://github.com/SiroSoft/siro-core/blob/main/docs/LOGGER.md) | Log levels, sanitization, audit |
| **Router** | [docs/ROUTER.md](https://github.com/SiroSoft/siro-core/blob/main/docs/ROUTER.md) | Routes, middleware, Route Attributes (PHP 8) |
| **JWT Auth** | [docs/JWT.md](https://github.com/SiroSoft/siro-core/blob/main/docs/JWT.md) | Access/Refresh tokens, key rotation |
| **Validation** | [docs/VALIDATION.md](https://github.com/SiroSoft/siro-core/blob/main/docs/VALIDATION.md) | Rules, custom messages |
| **CLI** | [docs/CLI.md](https://github.com/SiroSoft/siro-core/blob/main/docs/CLI.md) | 70 commands reference |
| **Security** | [docs/SECURITY.md](https://github.com/SiroSoft/siro-core/blob/main/docs/SECURITY.md) | CSP, CORS, CSRF, best practices |

---

## Test

```bash
# Core framework tests
cd vendor/sirosoft/core
php vendor/bin/phpunit --no-coverage              # 19,038 tests, 0 failures
php vendor/bin/phpstan analyse --level=max         # 0 errors
php vendor/bin/psalm --taint-analysis              # 0 errors
composer audit                                     # 0 vulnerabilities
php scripts/chaos-test.php                         # Chaos engineering
php scripts/health-check.php                       # System health

# Application tests
cd your-project/
php siro test                                       # 430 app tests
php siro test --coverage                            # With coverage
php siro benchmark                                  # Performance
```

### Verified Results

| Suite | Tests | Assertions | Status |
|-------|-------|-----------|--------|
| Core Unit | 988 | 2,547 | ✅ 0 failures |
| Core Fuzz | 17,851 | 28,849 | ✅ 0 failures |
| Core DAST | 157 | 166 | ✅ 0 failures |
| Core Integration | 42 | 90 | ✅ 0 failures |
| **Core Total** | **19,038** | **31,652** | **✅ 0 failures** |
| PHPStan Level Max | — | — | ✅ 0 errors |
| Psalm Level 1 | — | — | ✅ 0 errors |
| App Tests | 430 | 534 | ✅ |
| **Grand Total** | **19,468** | **32,186** | **✅** |

---

## Requirements

- PHP 8.2+
- ext-pdo, ext-json, ext-mbstring
- ext-redis (optional, for cache/rate limiter)
- ext-openssl (optional, for Encrypter)

---

## Use Cases

| Scenario | Why Siro |
|----------|----------|
| **REST API / Microservices** | 1ms boot, zero deps, JWT built-in, 70 CLI commands |
| **Startup MVP** | `make:crud` in one command, auth in 5 minutes |
| **High-throughput API (10K+ req/s)** | 488K ops/sec static route, 2KB memory/req |
| **SPA Backend (React/Vue)** | JWT + CORS + CSRF double-submit + API versioning |
| **Serverless (Lambda/CF)** | Zero deps, 1ms boot — ideal for cold starts |

---

## License

MIT © [SiroSoft](https://sirophp.com)

---

<div align="center">
  <sub>Built with ❤️ by SiroSoft Team</sub>
</div>
