# Siro API Framework v0.24.0

**Build a production-ready API with auth in 5 minutes.** Zero external dependencies. JWT built-in. CRUD generator. 70 CLI commands.

```bash
composer create-project sirosoft/api my-api
cd my-api
php siro migrate
php siro serve
```

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP 8.2+](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Tests](https://img.shields.io/badge/tests-1350%2B%20passing-brightgreen.svg)](tests/)
[![PHPStan](https://img.shields.io/badge/phpstan-level%207-0%20errors-brightgreen.svg)](https://github.com/SiroSoft/siro-core)

---

## Why Siro?

| Problem | Siro |
|---------|------|
| **Too many dependencies** | **Zero** runtime deps — no Guzzle, Monolog, Symfony |
| **Slow boot** | **~1ms** — 50x faster than Laravel |
| **JWT setup** | **Built-in** — algorithm pinning, key rotation, JTI blacklist |
| **API boilerplate** | `make:crud` — full CRUD trong 1 lệnh |
| **Security** | OWASP Top 10 mitigated **by default** |

## Quick Start (5 minutes)

```bash
# 1. Create project
composer create-project sirosoft/api my-api

# 2. Start coding
cd my-api
php siro key:generate
php siro serve
```

Your API is live at `http://localhost:8080` with auth routes ready.

## 6 commands → Full API with Auth

```bash
php siro key:generate          # Generate JWT secret
php siro make:auth              # Auth system (register, login, refresh)
php siro make:crud Product      # Full CRUD: Controller + Service + Repository + Model
php siro make:crud Order        # Another resource
php siro migrate                 # Create database tables
php siro serve                   # Start dev server
```

## What you get

| Feature | Status | Details |
|---------|--------|---------|
| JWT Auth | ✅ Built-in | Register, login, refresh, email verify, password reset |
| CRUD Generator | ✅ 1 command | `make:crud` → controller + service + repository + model + test |
| Database | ✅ SQLite/MySQL/PostgreSQL | Query Builder, ORM, Migrations, Relations |
| Validation | ✅ 15+ rules | required, email, unique, exists, custom rules |
| Rate Limiting | ✅ Redis + file fallback | Per-route throttling |
| CORS / CSP / CSRF | ✅ All built-in | Security headers, preflight, double-submit cookie |
| API Versioning | ✅ Header-based | `Accept: application/vnd.siro.v2+json` |
| Prometheus Metrics | ✅ Built-in | `/metrics` endpoint with counters + histograms |
| Cache | ✅ File + Redis | Query cache, route cache, config cache |
| Queue | ✅ DB-based | Async jobs with backoff, retry, timeout |
| File Storage | ✅ Local + S3 | Upload, serve, S3 with AWS Signature V4 |
| Debug | ✅ Tail/Replay/Trace | `log:tail`, `log:replay`, `debug:last` |
| CLI | **70 commands** | Make, migrate, cache, queue, log, deploy, benchmark |

## API Endpoints (after `make:auth`)

```
POST /api/auth/register       # Register
POST /api/auth/login          # Login
POST /api/auth/refresh        # Refresh token
POST /api/auth/logout         # Logout
GET  /api/auth/me             # Current user
GET  /api/users               # List users (paginated)
POST /api/users               # Create user
GET  /api/users/{id}          # Get user
PUT  /api/users/{id}          # Update user
DELETE /api/users/{id}        # Delete user
```

## CLI Commands (70)

```
make:*       Generate controllers, models, CRUD, auth, migrations...
db:*         Migrate, seed, show tables
cache:*      Config/route/env cache
log:*        Tail, trace, replay, stats, slow
queue:*      Work, retry, flush, status
debug:*      Health check, last error
system:      key:generate, serve, deploy, benchmark, doctor, test
```

## Full Documentation

- [Framework Core Docs](https://github.com/SiroSoft/siro-core)
- [Getting Started Guide](docs/getting-started.md)
- [API Reference](docs/api/README.md)

## Performance

```
Static route dispatch:    0.002ms (488K ops/sec)
Dynamic route dispatch:   0.009ms
Cold boot:                ~1ms (Linux + OPcache)
Memory per request:       ~2KB
```

## Requirements

- PHP 8.2+
- PDO, JSON, mbstring extensions

## License

MIT © SiroSoft
