<div align="center">
  <h1>⚡ Siro</h1>
  <p><strong>Production-first API framework for PHP.</strong><br>
  Debug real production requests from your terminal. Zero dependencies.</p>
</div>

<div align="center">

[![PHP 8.2+](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-745%20pass-brightgreen)](tests/)
[![MSI](https://img.shields.io/badge/MSI-83%25-brightgreen)](coverage/)
[![PHPStan](https://img.shields.io/badge/PHPStan-Level%20Max-brightgreen)](https://phpstan.org)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/sirosoft/api)](https://packagist.org/packages/sirosoft/api)
[![Coverage](https://img.shields.io/badge/coverage-80%25%20%2B-brightgreen)](coverage/)

</div>

---

## Quick start

```bash
# Windows
iwr https://sirophp.com/downloads/install.ps1 -UseBasicParsing | iex

# Linux/macOS
curl -sS https://sirophp.com/downloads/install.sh | bash

cd my-api
php siro key:generate && php siro make:auth && php siro migrate && php siro serve
```

Your API is live — JWT auth, user CRUD, migrations ran. No config files. No Postman.

```
> Tip: `php siro t GET /api/auth/me` — shorthand for `api:test`, auto-auth.
```

---

## Debug production in 1 command

```bash
php siro api:why POST /api/orders
```

```
  Request
  ────────────────────────────────────────────────────────
  Route:    POST /api/orders
  Status:   ✗ 500
  Duration: 143ms

  Middleware Pipeline
    └ ✗ OrderMiddleware       35ms ⚠ slow

  SQL Queries
    └ ⚠ UPDATE inventory      102ms ⚠ slow

  Exception
    PDOException: Deadlock found when trying to get lock

  Possible Cause
    • Concurrent transaction conflict
    • Missing retry logic for deadlock scenarios

  Suggested Fix
    ▸ Wrap transaction in retry loop (max 3 attempts)

  Replay
    [r] php siro replay siro_a1b2c3d4 --force
    [e] php siro replay siro_a1b2c3d4 --edit
    [d] php siro replay siro_a1b2c3d4 --diff
    [t] php siro make:test --from-trace=siro_a1b2c3d4

  ⚠ Side-effect risks detected: 2 DB writes, 1 outbound HTTP call
  → Replay blocked by default. Use --force to execute.
```

**No other framework — PHP, Node, Go, Rust, Python, Ruby — has this flow.**

> Replay is risk-aware: Siro analyzes captured SQL writes, outbound HTTP calls, and queued jobs before replay. Risky traces require `--force`.

---

## Build → Ship → Why → Replay → Fix → Test → Regression

```bash
# 1. Build — CRUD in 2 seconds
php siro make:crud Product

# 2. Ship — deploy
php siro deploy

# 3. Why — debug production failure
php siro why

# 4. Replay & diff — so sánh trước/sau fix (risk-aware)
php siro replay siro_a1b2c3d4 --diff
```

```
  === BEFORE ===                    === AFTER ===
  Status: 500                       Status: 200
  Body: {"success":false}           Body: {"success":true,"data":{"id":100}}
                                    ✅ Fixed!
```

```bash
# 5. Fix — watch mode, auto re-test
php siro fix

# 6. Test — generate from real trace
php siro make:test --from-trace=siro_a1b2c3d4

# 7. Regression — verify không break
php siro test:regression --fail
```

---

## Killer features

| Command | What it does | Why it matters |
|---------|-------------|----------------|
| `api:why` | Debug by method + path | Instant root cause — no log diving |
| `replay` | Replay exact production request | Reproduce bugs in 5 seconds |
| `make:test --from-trace` | Generate PHPUnit test from real trace | Every bug becomes a permanent regression test |
| `test:regression` | Replay all traces, detect regressions | System gets stronger over time |
| `make:crud` | Full CRUD in 2 seconds | Model + Controller + Migration + Routes + Tests |
| `fix` | Watch mode — auto re-test on save | Fix and verify in one loop |

---

## What's inside

```
my-api/
├── app/Controllers/    # 7 pre-built (Auth + 6 CRUD)
├── app/Models/         # 6 pre-built (User, Product, Category...)
├── app/Services/       # 8 pre-built (BaseService pattern)
├── database/           # 13 migrations + 2 seeders
├── tests/              # **745 passing tests**
├── docker-compose.yml  # FrankenPHP + Nginx + Caddy
├── Dockerfile          # Production build
└── k8s/                # Helm chart
```

**Not an empty skeleton — production-grade, ready to deploy with 745+ tests.**

---

## Quality & performance

| Gate | Result | | Metric | |
|------|--------|--|--------|---|
| Core unit/integration | **2,846 — 0 failures** | | Cold boot (Linux) | **~0.5 ms** (est.) |
| App tests | **745 — 0 failures** | | Cold boot (Win) | **~2.4 ms** (measured) |
| Fuzz tests | **17,981 — 0 failures** | | Route dispatch | **~361K ops/sec** |
| DAST security | **157 — 0 failures** | | Memory baseline | **~4 MB** |
| PHPStan | **Level Max — 0 errors** | | Full-stack | **~404K ops/sec** |
| Composer audit | **0 vulnerabilities** | | | |
| Mutation testing | **MSI 83%** | | Line coverage | **80%+** |
| Mutation testing | **MSI 83%** | | Line coverage | **80%+** |

---

## Built-in features

Zero packages needed. JWT auth, ORM, migrations, queue, mail, cache, validation, rate limiting, CSP, CORS, CSRF, OpenAPI, Prometheus metrics, CLI (95 commands). [Full list →](https://sirophp.com/features)

---

## Deployment

```bash
docker compose up -d    # FrankenPHP, HTTP/2, auto HTTPS
docker build -f Dockerfile.frankenphp -t my-api .
```

Docker + Kubernetes (Helm chart) included.

---

## Philosophy

Traditional frameworks focus on **writing code**.  
Siro focuses on **operating APIs in production**.

```bash
# Most frameworks:  log → guess → redeploy → wait → repeat
# Siro:            trace → replay → fix → regression → done
```

---

## Requirements

PHP 8.2+ with `ext-pdo`, `ext-json`, `ext-mbstring`.

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
