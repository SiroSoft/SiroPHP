<div align="center">
  <img src="https://raw.githubusercontent.com/SiroSoft/SiroPHP/main/art/logo.svg" alt="Siro PHP Framework" width="200"/>
  <h1>Siro API Framework v0.26.0</h1>
  <p><strong>The Fastest, Lightest, Most Secure PHP Micro-Framework</strong></p>
  <p>Zero dependencies • Sub-millisecond boot • JWT built-in • 70 CLI commands • OWASP Top 10 mitigated</p>
</div>

<div align="center">

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP 8.2+](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-1436%20passing-brightgreen.svg)](tests/)
[![PHPStan](https://img.shields.io/badge/phpstan-level%207-0%20errors-brightgreen.svg)](https://github.com/SiroSoft/siro-core)
[![Security](https://img.shields.io/badge/security-audited-brightgreen)](https://sirophp.com)
[![Packagist](https://img.shields.io/packagist/v/sirosoft/api?color=blue)](https://packagist.org/packages/sirosoft/api)

</div>

---

```bash
# Zero → Production API with Auth in 5 minutes
composer create-project sirosoft/api my-api && cd my-api && php siro serve
#                                           ^
#                        http://localhost:8080 with JWT auth + CRUD
```

---

## 🚀 Why Siro?

| You struggle with | Siro solves it |
|------------------|----------------|
| **Laravel/Symfony too heavy** (~60-100 dependencies) | **Zero** runtime dependencies. Just PHP + PDO |
| **Slow boot** (50-80ms per request) | **~1ms** cold boot. 50-80x faster |
| **JWT auth takes hours to setup** | **Built-in**. Algorithm pinning, key rotation, JTI blacklist |
| **Manual CRUD boilerplate** | **1 command**: `make:crud Product` generates Controller + Service + Repository + Model + Migration + Test |
| **Security left to developers** | **OWASP Top 10** mitigated from the start: CSP, CORS, CSRF, Rate Limit, SQLi, XSS |
| **Dependency vulnerabilities** | **Zero transitive dependencies**. Composer audit = 0 issues |

---

## 📦 6 Commands → Full API with Auth

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

# API của bạn đã ONLINE với đầy đủ auth + CRUD ✅
```

---

## ⚡ Best of Siro

### 🛡️ Security built-in (Không cần config gì thêm)
```
🔐 JWT Algorithm Pinning    — Không bao giờ trust token's alg header
🔄 JWT Key Rotation          — Previous secret với version tracking
⛔ JTI Blacklist             — Revoke từng token riêng lẻ
🛡️ CSP Middleware             — Content-Security-Policy strict-dynamic
🌐 CORS                      — Configurable origins, credentials
🍪 CSRF                      — Session-based + Double-submit cookie cho SPA
🚦 Rate Limiter              — Redis + file fallback
📋 Audit Log                 — SIEM-ready security.log
🔍 SQL Injection             — 100% prepared statements
❌ XSS                        — htmlspecialchars + CSP headers
```

### ⚡ Performance không tưởng cho PHP

```
Benchmark                          Kết quả
─────────────────────────────────────────────────────
  Static route dispatch           0.002ms  (488K ops/sec)
  Dynamic route dispatch          0.009ms
  Middleware pipeline (10 lớp)    0.012ms  (negligible)
  Cold boot (chưa có cache)       ~1ms     (Linux + OPcache)
  1000 routes registration        1.2ms
  Memory mỗi request              ~2KB     (không leak)
  JSON serialize (1000 items)     1.8ms
  Query SQLite (500 rows)         0.5ms
  Full App lifecycle              ~0.4ms   (2.300 req/sec)
```

### 🖥️ 70 CLI Commands

```
  make:*       23 commands      make:auth, make:crud, make:controller, make:model...
  db:*          5 commands      migrate, rollback, seed, show
  log:*         9 commands      tail, trace, replay, stats, slow, export, cleanup, top
  queue:*       4 commands      work, retry, flush, status
  cache:*       3 commands      config:cache, config:clear, env:cache
  server:*      4 commands      serve, frankenphp:serve, live, deploy
  debug:*       2 commands      debug:last, debug:health
  system:*     20 commands      key:generate, benchmark, route:list, test, doctor...
```

Tất cả commands có `--help`, tự động suggest khi gõ sai (Levenshtein).

---

## 🏗️ Architecture

```
Request → Router → [Middleware Pipeline] → Controller → Service → Repository → Model → DB
                                                              ↕
                                                           Resource → JSON Response
```

```
📁 app/
├── Controllers/     # Nhận request, trả response
├── Services/        # Business logic
├── Repositories/    # Database access
├── Models/          # ORM
├── Resources/       # JSON transform
├── Middleware/       # Auth, JSON, SecurityHeaders
└── Exceptions/      # Custom exceptions

📁 config/           # app.php, database.php, jwt.php, cors.php, cache.php, mail.php
📁 routes/           # api.php
📁 database/         # migrations/
📁 storage/          # logs/, cache/, sessions/
📁 public/           # index.php (entry point)
```

---

## 🔌 API Endpoints (After `make:auth`)

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

## 🧰 Full Feature Breakdown

| Tính năng | Chi tiết |
|-----------|----------|
| **JWT Auth** | Access + Refresh tokens, algorithm pinning (HS256/RS256), key rotation, JTI blacklist, audience validation |
| **CRUD Generator** | `php siro make:crud Product` → Controller + Service + Repository + Model + Resource + Migration + Test |
| **Query Builder** | SELECT, JOIN, WHERE, GROUP BY, HAVING, subqueries, pagination, aggregates |
| **ORM** | HasOne, HasMany, BelongsTo, BelongsToMany, eager loading, soft deletes |
| **Migrations** | Create, rollback, status. Supports MySQL, PostgreSQL, SQLite |
| **Validation** | 15+ rules: required, email, unique, exists, min, max, confirmed, in, regex, file, image, date, url. Custom rules + custom messages |
| **Cache** | File + Redis drivers, auto-prefix, query/route/config caching |
| **Rate Limiting** | Per-route configurable, Redis primary + file fallback |
| **Middleware** | Auth, CORS, CSP, CSRF, ETag, Version, Metrics, Audit, Throttle, Idempotency, SecurityHeaders, JSON |
| **File Storage** | Local filesystem, S3-compatible (AWS Signature V4), upload validation (MIME + size) |
| **Queue** | DB-based jobs, exponential backoff, timeout, priority, failed job retry |
| **Mail** | Sendmail + SMTP (STARTTLS, AUTH LOGIN), async queuing, HTML + attachments |
| **Events** | Pub/sub, wildcards, one-time listeners. Model events: creating, created, saving, saved, deleting, deleted |
| **Debug** | `X-Siro-Trace-Id`, request replay (`log:replay`), slow query detection, log sanitization, `debug:last` |
| **API Versioning** | Header-based via `Accept: application/vnd.siro.v2+json`, route overrides per version |
| **Prometheus** | `/metrics` endpoint, auto-track request count, duration histogram, status codes |
| **CLI** | 70 commands với help, aliases, Levenshtein suggestion khi gõ sai |
| **Encryption** | AES-256-CBC, HKDF key separation, Encrypt-then-MAC, `hash_equals` timing-safe |

---

## 🐳 Production Deployment

```bash
# FrankenPHP (multi-worker, HTTP/2, HTTP/3, auto HTTPS)
docker compose up frankenphp

# Or build yourself
docker build -f Dockerfile.frankenphp -t my-api .
docker run -p 80:80 -p 443:443 -v .env:/app/.env my-api
```

---

## 📚 Documentation

| Module | Link | Mô tả |
|--------|------|-------|
| **Database** | [docs/DATABASE.md](https://github.com/SiroSoft/siro-core/blob/main/docs/DATABASE.md) | QueryBuilder, Models, Migrations, Relations |
| **Cache** | [docs/CACHE.md](https://github.com/SiroSoft/siro-core/blob/main/docs/CACHE.md) | File/Redis, query caching |
| **Logger** | [docs/LOGGER.md](https://github.com/SiroSoft/siro-core/blob/main/docs/LOGGER.md) | Log levels, sanitization, audit |
| **Router** | [docs/ROUTER.md](https://github.com/SiroSoft/siro-core/blob/main/docs/ROUTER.md) | Routes, middleware, Route Attributes (PHP 8) |
| **JWT Auth** | [docs/JWT.md](https://github.com/SiroSoft/siro-core/blob/main/docs/JWT.md) | Access/Refresh tokens, key rotation |
| **Validation** | [docs/VALIDATION.md](https://github.com/SiroSoft/siro-core/blob/main/docs/VALIDATION.md) | Rules, custom messages |
| **CLI** | [docs/CLI.md](https://github.com/SiroSoft/siro-core/blob/main/docs/CLI.md) | 70 commands reference |
| **Security** | [docs/SECURITY.md](https://github.com/SiroSoft/siro-core/blob/main/docs/SECURITY.md) | CSP, CORS, CSRF, best practices |

---

## 🧪 Test

```bash
php siro test                  # Run all tests
php siro test --coverage       # With coverage report
php siro test --filter=User    # Filter by name
php siro benchmark             # Performance benchmark
```

Current: **1436+ tests passing** (1005 core + 431 app), 0 failures.

---

## 📋 Requirements

- PHP 8.2+
- ext-pdo, ext-json, ext-mbstring
- ext-redis (optional, cho cache/rate limiter)
- ext-openssl (optional, cho Encrypter)

---

## 🎯 Use Cases

| Scenario | Tại sao chọn Siro |
|----------|-------------------|
| **REST API / Microservices** | Boot 1ms, zero deps, JWT built-in, 70 CLI commands |
| **Startup MVP** | `make:crud` trong 1 lệnh, auth trong 5 phút |
| **High-throughput API (10K+ req/s)** | 488K ops/sec static route, 2KB memory/req |
| **SPA Backend (React/Vue)** | JWT + CORS + CSRF double-submit + API versioning |
| **Serverless (Lambda/CF)** | Zero deps, boot 1ms — lý tưởng cho cold start |

---

## 📄 License

MIT © [SiroSoft](https://sirophp.com)

---

<div align="center">
  <sub>Built with ❤️ by SiroSoft Team</sub>
</div>
