<div align="center">
  <h1>⚡ Siro</h1>
  <p><strong>Production-first API framework for PHP.</strong><br>
  Zero dependencies. 60 seconds to auth + CRUD. Debug production from your terminal.</p>
</div>

<div align="center">

[![PHP 8.2+](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-19.496%20pass-brightgreen)](tests/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-brightgreen)](https://phpstan.org)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/sirosoft/api)](https://packagist.org/packages/sirosoft/api)

</div>

---

## 60 seconds to a working API with auth

```bash
composer create-project sirosoft/api my-api
cd my-api
php siro key:generate && php siro make:auth && php siro migrate && php siro serve
```

That's it. Your API is live at `http://localhost:8080` with:

```http
POST /api/auth/register     {"name":"Demo","email":"demo@test.com","password":"secret123"}
POST /api/auth/login        {"email":"demo@test.com","password":"secret123"}
POST /api/auth/refresh      {"refresh_token":"..."}
POST /api/auth/logout
GET  /api/auth/me
```

No packages to install. No config files to write. No Postman setup.

> Tip: `php siro t GET /api/auth/me` — shorthand for `api:test`, auto-auth.

---

## What you get out of the box

```
my-api/
├── app/
│   ├── Controllers/      # 7 pre-built (Auth + 6 CRUD)
│   ├── Models/           # 6 pre-built (User, Product, Category, Tag, Order, Post)
│   ├── Services/         # 8 pre-built (BaseService pattern)
│   ├── Middleware/       # 2 custom middleware
│   └── Exceptions/       # Exception handler
├── database/
│   ├── migrations/       # 13 migrations
│   └── seeders/          # 2 seeders
├── routes/
│   └── api.php           # 20+ routes
├── tests/                # 462 passing tests
├── docker-compose.yml    # FrankenPHP + Nginx + Caddy
├── Dockerfile            # Production build
├── k8s/                  # Helm chart
└── openapi.yaml          # OpenAPI 3.0 spec
```

Not an empty skeleton — a **production-grade API project**, ready to deploy.

---

## Debug a production bug in 4 commands

```bash
# 1. Search
php siro log:trace --path=/api/orders --status=500 --since=30m

# 2. Replay & diff — so sánh trước/sau fix
php siro replay siro_a1b2c3d4 --diff
```

```
  === BEFORE ===                    === AFTER ===
  Status: 500                       Status: 200
  Body: {"success":false}           Body: {"success":true,"data":{"id":100}}
                                    ✅ Fixed!
```

```bash
# 3. Generate test từ bug thật
php siro make:test --from-trace=siro_a1b2c3d4
```

```
Generated: tests/Feature/FromTraceDemo_...Test.php
  php vendor/bin/phpunit --filter=FromTraceDemo_...
  → OK (1 test, 6 assertions)
```

```bash
# 4. Regression — verify không break
php siro test:regression --fail
```

---

## What does `api:why` look like?

```bash
php siro api:why POST /api/orders
```

```
  Request
  ────────────────────────────────────────────────────────
  Route:    POST /api/orders
  Status:   ✗ 500
  Duration: 143ms
  Trace ID: siro_a1b2c3d4
  ────────────────────────────────────────────────────────

  Middleware Pipeline
    ├ ✓ AuthMiddleware        2.1ms
    ├ ✓ RateLimitMiddleware   0.8ms
    ├ ✓ CSRFMiddleware        0.4ms
    └ ✗ OrderMiddleware       35ms   ⚠ slow

  SQL Queries
    ├ ▸ SELECT            products  12ms
    ├ ▸ INSERT            orders    8ms
    └ ⚠ UPDATE            inventory  102ms   ⚠ slow
      Total SQL: 122ms

  Exception
    SQLSTATE[23000]: Deadlock found; try restarting transaction

  Possible Cause
    • Concurrent transaction conflict
    • Missing retry logic for deadlock scenarios

  Suggested Fix
    ▸ Wrap transaction in retry loop (max 3 attempts)
    ▸ Reduce transaction scope
    ▸ php siro replay siro_a1b2c3d4 --edit

  Response Source
    └ Controller::store

  Replay
    [r]  php siro replay siro_a1b2c3d4 --force
    [e]  php siro replay siro_a1b2c3d4 --edit
    [d]  php siro replay siro_a1b2c3d4 --diff
    [t]  php siro make:test --from-trace=siro_a1b2c3d4

  ────────────────────────────────────────────────────────
```

No other framework — PHP, Node, Go, Rust, Python, Ruby — has this flow.

---

## Killer features

| Feature | What it does | Why it matters |
|---------|-------------|----------------|
| **`api:why`** | Debug any request by method + path | Instant root cause — no log diving |
| **`replay`** | Replay exact production request locally | Reproduce bugs in 5 seconds |
| **`make:test --from-trace`** | Generate PHPUnit test from real trace | Every bug becomes a permanent regression test |
| **`test:regression`** | Replay all traces, detect regressions | System gets stronger over time |
| **`make:crud`** | Full CRUD in 2 seconds | Model + Controller + Migration + Routes + Tests |
| **`fix`** | Watch mode — auto re-test on save | Fix and verify in one loop |

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

---

## Built-in features (zero packages needed)

| Category | What you get |
|----------|-------------|
| **Auth** | JWT access+refresh, algorithm pinning (HS256/RS256), key rotation, API keys |
| **CLI** | 80 commands: `make:crud`, `make:auth`, `migrate`, `log:replay`, `api:test`... |
| **ORM** | Active Record, all relation types, eager loading, soft deletes, identity map |
| **Security** | CSP, CORS, CSRF, rate limiting, audit log, SQLi prevention (prepared statements) |
| **Debug** | Request replay, trace search, `api:why`, `db:why`, N+1 detection |
| **Database** | Query Builder, migrations, SQLite/MySQL/PostgreSQL |
| **Cache** | File + Redis drivers |
| **Queue** | DB-based jobs, exponential backoff, priority, retry |
| **Mail** | SMTP (STARTTLS), sendmail, async queuing, attachments |
| **Validation** | 15+ rules, FormRequest |
| **Events** | Pub/sub, wildcards, model lifecycle hooks |
| **Storage** | Local filesystem, S3-compatible |
| **API Tools** | OpenAPI spec generation, Postman collection, Prometheus metrics |

---

## Performance

```
Cold boot (Linux + OPcache):     ~0.5 ms
Cold boot (Windows, no OPcache): ~2.4 ms
Route dispatch static O(1):      ~0.003 ms (~300K ops/sec)
Full-stack (warm route+response): ~0.003 ms (~360K ops/sec)
Memory (framework baseline):      ~4 MB
```

Detailed benchmarks: [BENCHMARK.md](https://github.com/SiroSoft/siro-core/blob/main/BENCHMARK.md)

---

## Deployment

```bash
# Production — FrankenPHP, multi-worker, auto HTTPS
docker compose up -d

# Or build yourself
docker build -f Dockerfile.frankenphp -t my-api .
docker run -p 80:80 -p 443:443 my-api
```

FrankenPHP + Docker + Kubernetes (Helm chart) included.

---

## Philosophy

Traditional frameworks focus on **writing code**.

Siro focuses on **operating APIs in production**.

```bash
# Most frameworks: → log file → guess → add logging → redeploy → wait → repeat
# Siro:            → log:trace → replay → fix → test:regression → done
```

---

## Requirements

PHP 8.2+ with `ext-pdo`, `ext-json`, `ext-mbstring`.

---

## When to use Siro

| ✅ Good fit | ❌ Not a fit |
|------------|-------------|
| REST API / microservices | Full-stack web apps (Blade, Livewire) |
| High-throughput APIs | Need large ecosystem packages |
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
