# Release Notes

## v0.28.1 — Migration & QueryBuilder Enhancements (2026-05-19)

### 🏗 Migration System
- **`migrate:fresh`**: Drop all tables + re-migrate with `--seed` option
- **`migrate:status --pending`**: Show only pending migrations
- **`foreignId()`** helper added to Blueprint
- File naming standardized to `Y_m_d_His`

### 🔧 QueryBuilder
- **`groupByRaw()`** / **`havingRaw()`**: Raw SQL functions in GROUP BY, HAVING
- **`DB::raw()`**: Raw expression facade for any clause

### 🖥 CLI
- **`registerCommand()`** / **`registerCommands()`**: Register custom commands from app code
- **`env:check`**: MySQL version check (> 8.0 for JSON column support)
- **`log:trace`**: 4 new filters — `--ip`, `--path`, `--error`, `--since`

### 🧰 Other
- **`Response::raw()`**: Auto-detect Content-Type (JSON, HTML)
- 19,496 tests passing (19,034 core + 462 skeleton), 0 failures
- PHPStan level max: 0 errors

---

## v0.28.0 — Comprehensive Security Audit (2026-05-19)

### 🛡️ Security Hardening
- 7 CRITICAL fixes: RCE, SMTP injection, Code injection, SQL compat, HMAC, Listeners, Tests
- 8 HIGH fixes: SSRF, MITM, Rate limit bypass, JTI blacklist, Redis cleanup, LOCK_EX
- **Env system**: 5-tier priority chain (`.env.siro` → `.env` → `.env.{env}` → `.env.local` → `.env.{env}.local`)
- **Log Replay**: `--edit`, `--diff`, `--set`, `--dry-run` modes
- 42/42 penetration tests passed (OWASP Top 10 full coverage)
- PHPStan level max: 0 errors | 19,496 tests — 0 failures

---

## v0.23.0 — API Versioning, ETag, Metrics (2026-05-12)

### 🆕 New
- **API Versioning**: `version` middleware on `/api` group
- **ETag**: Auto `304 Not Modified` for cached responses
- **Metrics**: GET `/metrics` endpoint (OpenMetrics format)
- **Auth caching**: User fetched once per request (not per middleware)

### ⚡ Performance
- sirosoft/core v0.23: sub-1ms boot, 3.1M JSON responses/sec
- 426 tests passing, 0 failures

---

## v0.22.0 — Final Audit (2026-05-11)

- All 751 PHPStan baseline errors eliminated
- XSS fixes in email templates
- All 7 controllers extend Controller base class
- BaseRepository + BaseService pattern
- 427 tests passing

---

## v0.21.0 — Server-Ready (2026-05-10)

- Production deployment ready
- JWT auth with refresh tokens
- MySQL/PostgreSQL/SQLite support
- CRUD scaffolding with make:crud
- 872 framework tests + 426 skeleton tests
