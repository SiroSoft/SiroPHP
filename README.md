<div align="center">
  <h1>⚡ Siro</h1>
  <p><strong>API-first PHP framework with built-in request replay.</strong><br>
  Zero dependencies · Sub-millisecond boot · 19,496 tests · OWASP Top 10 mitigated</p>
</div>

<div align="center">

[![PHP 8.2+](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-19.496%20pass-brightgreen)](tests/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-brightgreen)](https://phpstan.org)
[![Psalm](https://img.shields.io/badge/Psalm-Level%201-brightgreen)](https://psalm.dev)
[![Mutation](https://img.shields.io/badge/mutation-MSI%20≥80%25-brightgreen)](https://infection.github.io)
[![Security](https://img.shields.io/badge/security-OWASP%20Top%2010-brightgreen)](docs/SECURITY.md)
[![Packagist](https://img.shields.io/packagist/v/sirosoft/api)](https://packagist.org/packages/sirosoft/api)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![IDE Helper](https://img.shields.io/badge/IDE-autocomplete-brightgreen)](_ide_helper.php)
[![Shell Completion](https://img.shields.io/badge/shell-bash%20%7C%20zsh-blue)](siro-completion.bash)

</div>

```bash
# 5 commands → production API with auth
composer create-project sirosoft/api my-api && cd my-api
php siro key:generate && php siro make:auth && php siro migrate && php siro serve
# 🚀 http://localhost:8080 — JWT auth + CRUD ready
```

---

## First-run experience that doesn't suck

```bash
# Tab completion works out-of-box
php siro mak + Tab → make:crud

# IDE autocomplete for all facades
Route::get(), DB::table(), Cache::get(), Event::dispatch()

# Errors that tell you what's wrong, not just "500"
# "Token has expired" vs "Token signature is invalid"
```

---

Debug a production bug without a trace ID

**Every framework logs errors. Siro lets you replay them.**

```bash
# 1. Search — customer chỉ nhớ "lúc đặt hàng bị lỗi", không có trace ID
php siro log:trace --path=/api/orders --status=500 --since=1h

# 2. Inspect — xem full context: headers, body, SQL queries, timing
php siro log:trace siro_a1b2c3d4

# 3. Replay — dry-run mặc định, an toàn trên production
php siro log:replay siro_a1b2c3d4

# 4. Edit + Diff — sửa body, test fix, so sánh kết quả
php siro log:replay siro_a1b2c3d4 --edit --diff
```

No other framework — PHP, Node, Go, Rust, Python, Ruby — has this flow.

---

## Why Siro?

| Pain point | Siro |
|-----------|------|
| **Laravel/Symfony too heavy** | **Zero** runtime dependencies. Just PHP + PDO. |
| **50-80ms boot per request** | **~1ms** cold boot. 50-80x faster. |
| **JWT auth takes hours** | **Built-in**. Algorithm pinning, key rotation, token revocation. |
| **N+1 kills performance** | **Auto-detected** with `php siro why`. Identity map + eager loading. |
| **Manual CRUD boilerplate** | **1 command**: `make:crud Product` → Controller + Service + Repository + Model + Migration + Test. |
| **Security audits find issues** | **9 Critical fixes** applied from world-class audit. OWASP Top 10 mitigated. |
| **Testing takes too long** | **462 app tests in 34s** + 19,034 core tests. PHPStan level max. |
| **Dependency vulnerabilities** | **Zero** transitive dependencies. `composer audit` = 0 issues. |

---

## Quick start

```bash
# 1. Create project
composer create-project sirosoft/api my-api
cd my-api

# 2. Generate keys + auth
php siro key:generate
php siro make:auth

# 3. Create your first resource
php siro make:crud Product

# 4. Migrate + serve
php siro migrate
php siro serve --port=8080
```

```http
POST /api/auth/register   {"name":"Demo","email":"demo@test.com","password":"secret123"}
POST /api/auth/login      {"email":"demo@test.com","password":"secret123"}
GET  /api/products        [Authorization: Bearer <token>]
POST /api/products        {"name":"Laptop","price":999}
```

---

## Built-in features (zero packages needed)

| Category | What you get |
|----------|-------------|
| **Auth** | JWT access+refresh tokens, algorithm pinning (HS256/RS256), key rotation, JTI blacklist, API keys |
| **CLI** | 72 commands: `make:crud`, `make:auth`, `migrate`, `log:replay`, `api:test`, `benchmark`... |
| **ORM** | Active Record, HasOne/HasMany/BelongsTo/BelongsToMany, eager loading, soft deletes, identity map |
| **Security** | CSP, CORS, CSRF, rate limiting, audit log, SQLi prevention (100% prepared statements), XSS protection |
| **Debug** | Request replay, trace search by IP/path/error, `php siro why`, N+1 detection, log sanitization |
| **Database** | Query Builder, migrations, SQLite/MySQL/PostgreSQL, pagination, row locking |
| **Cache** | File + Redis drivers, auto-prefix, query/route/config caching |
| **Queue** | DB-based jobs, exponential backoff, timeout, priority, retry |
| **Mail** | SMTP (STARTTLS), sendmail, async queuing, attachments |
| **Validation** | 15+ rules: required, email, unique, exists, min, max, regex, file, image... |
| **Events** | Pub/sub, wildcards, one-time listeners, model lifecycle hooks |
| **Storage** | Local filesystem, S3-compatible (AWS Signature V4) |
| **API Tools** | OpenAPI spec generation, Postman collection, Prometheus metrics, API versioning |
| **Testing** | PHPUnit base test case, in-memory SQLite, HTTP test helpers, transaction rollback |

---

## Performance

```
Benchmark                        Result
─────────────────────────────────────────────────────
  Cold boot                      ~1ms
  Route dispatch (static)        0.002ms  (488K ops/sec)
  Route dispatch (1000 routes)   0.002ms  (O(1))
  Middleware (10 layers)         0.012ms
  Memory per request             ~2KB
  Full lifecycle                 0.29ms   (3,447 req/sec)
```

Compare: Laravel ~50K ops/sec → **Siro ~864K ops/sec** (17x faster).

---

## Quality

| Gate | Result |
|------|--------|
| Core tests | 19,034 — **0 failures** |
| App tests | 462 — **0 failures** |
| Fuzz tests | 17,851 — **0 failures** |
| DAST security | 157 — **0 failures** |
| Mutation testing | **MSI ≥80%** |
| PHPStan | **Level Max — 0 errors** |
| Psalm | **Level 1 — 0 errors** |
| Composer audit | **0 vulnerabilities** |
| Supply chain | SLSA + SBOM (CycloneDX) |

---

## Deployment

```bash
# Production (FrankenPHP — multi-worker, HTTP/2, HTTP/3, auto HTTPS)
docker compose up -d

# Or build yourself
docker build -f Dockerfile.frankenphp -t my-api .
docker run -p 80:80 -p 443:443 my-api
```

---

## Requirements

PHP 8.2+ with `ext-pdo`, `ext-json`, `ext-mbstring`. Optional: `ext-redis`, `ext-openssl`.

---

## When to use Siro

| ✅ Good fit | ❌ Not a fit |
|------------|-------------|
| REST API / microservices | Full-stack web apps (Blade, Livewire) |
| High-throughput (10K+ req/s) | Need large ecosystem packages |
| Startup MVP (fast iteration) | Team already deep in Laravel |
| SPA backend (React, Vue) | Need admin panel out-of-box |
| Serverless (Lambda, CF) | — |

---

<div align="center">
  <p>
    <a href="https://sirophp.com">Website</a> ·
    <a href="https://sirophp.com/docs">Docs</a> ·
    <a href="https://github.com/SiroSoft/siro-core">Core</a> ·
    <a href="https://packagist.org/packages/sirosoft/api">Packagist</a>
  </p>
  <p>MIT © <a href="https://sirophp.com">SiroSoft</a></p>
</div>
