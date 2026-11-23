# Upgrade Guide

## v1.0.x → v1.1.0

### Overview

Error-response hardening + throttle fail-closed default. No data migration
needed. Three behavioral changes can break existing clients/monitors — read
the items below before upgrading production.

### Behavioral changes

#### 1. Throttle defaults to `fail_closed` outside local env
`routes/api.php` now forces `THROTTLE_FALLBACK=fail_closed` whenever the
configured value is neither `file` nor `fail_closed` and `APP_ENV` is not
`local`. A degraded rate-limit backend (e.g. Redis down) now rejects
requests instead of silently letting them through unthrottled.

**Impact**: if your throttle backend is down, clients see rate-limit
rejections instead of unthrottled access. This is the intended behavior.

**Opt-out** (only if you accept fail-open): set `THROTTLE_FALLBACK=file`
explicitly in `.env`. The testing env is exempt (CI has no Redis).

#### 2. DB connection failure returns 503 (was 500)
A failed DB connection now returns HTTP 503 instead of 500, so alerting can
distinguish "dependency down" from "application error".

**Impact**: update any monitor/alert that keys on 500s for DB outages to
also watch 503s.

#### 3. Error responses no longer leak internals
404/409/400/500 responses now carry generic messages only — no stack traces,
no driver details, no exception text (`app/Exceptions/Handler.php`,
`routes/api.php` settings endpoint).

**Impact**: clients that parsed exception text out of error responses will
see generic messages instead. Debug details are still available server-side
via `APP_DEBUG=true` logs/traces.

#### 4. Auth required on status routes
`GET /health` and `GET /health/ready` now require the `auth` middleware
(`GET /health/live` stays public for liveness probes).

**Impact**: unauthenticated health probes (load balancers, uptime monitors)
will get 401. Point liveness checks at `/health/live`, or send an
authenticated request to `/health` / `/health/ready`.

### Steps

```bash
composer update sirosoft/core   # requires sirosoft/core v1.1.0+
php vendor/bin/phpunit          # 761 tests, all green expected
```

### Notes

- Requires `sirosoft/core` v1.1.0+ (throttle 503 semantics, `malformed_body`).
- No breaking changes to application code: controllers, services, models and
  migrations work unchanged.

---

## v0.35.x → v0.40.0

### Overview

Non-breaking upgrade. Core engine bumped to `sirosoft/core ^0.40.0`
which adds enterprise-grade test infrastructure and security hardening.

### Steps

```bash
composer update sirosoft/core
php vendor/bin/phpunit          # 742 tests, all green expected
```

### Notes

- `config/deploy.json` is no longer tracked by git (keep your local copy).
- New mutation-test suites live in `tests/unit/*MutationTest.php`.
- Model identity map now invalidates correctly on insert/update/delete.

---

## v0.27.x → v1.0.0

### Overview

v1.0.0 is the first stable release. No breaking changes from v0.27.x —
all existing code continues to work without modification.

### New Features

| Feature | Description |
|---------|-------------|
| `Database::raw()` | Raw SQL expression support in select/groupBy |
| `Database::execStatement()` | Execute DDL/SET statements directly |
| `orderByRaw()` | ORDER BY with raw SQL expression |
| `groupBy()` variadic | `groupBy('col1', 'col2')` now supported |
| `LOG_LEVEL` env | Set to `error` to suppress debug logs entirely |
| `LOG_MAX_SIZE_MB` env | Total log size limit; oldest files auto-deleted |

### Behavioral Changes

#### Log directory restructured
Logs are now organised into subdirectories:

```
storage/logs/
  daily/2026-05/     ← month-partitioned daily files
  main/              ← cumulative files (rotated at 50MB)
  traces/2026/05/18/ ← date + hash-prefix partitioned
```

**Impact**: Existing log files in the old flat structure are no longer
read by CLI commands (`log:tail`, `log:stats`, etc.). Old files can be
safely moved or deleted after verifying the new structure works.

**If you use custom scripts that read log paths**, update:
- `storage/logs/error-*.log` → `storage/logs/daily/*/error-*.log`
- `storage/logs/slow.log` → `storage/logs/main/slow.log`
- `storage/logs/traces/*.json` → `storage/logs/traces/*/*/*/*.json`

#### Database::execStatement() replaces raw PDO calls
```php
// Before
$pdo = Database::connection();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

// After (v1.0)
Database::execStatement('SET FOREIGN_KEY_CHECKS = 0');
```

#### Model::find() identity map
The identity map now initialises properly, preventing a `count(null)` error
on first access. No code changes needed.

### Removed

- **`demo-v1.0/` tests**: Removed (project no longer maintained).
- **`Dockerfile.dev` tests**: Skipped (use `docker/Dockerfile.frankenphp`).

### New Environment Variables

Add to your `.env` file as needed:

```env
# Suppress debug logs in production (default: debug)
LOG_LEVEL=error

# Total log storage limit in MB (default: 1024)
LOG_MAX_SIZE_MB=2048
```

### Deprecations (none)

No features are deprecated in v1.0.0.

---

## Upgrading from v0.26.x

Follow the v0.27.x → v1.0.0 guide above, plus:

### v0.27.0 changes
- `JWT::encodeAccess()` and `JWT::encodeRefresh()` signatures changed
  (see JWT.md for details).
- `Siro\Core\Auth\JWT` now requires `JWT_SECRET` env var.

### v0.27.1 changes
- `ModelQueryBuilder::where()` now correctly passes 2 or 3 args.
  If you overrode `where()` in a subclass, verify your signature matches.

### v0.27.2 changes
- Log directory restructured (see above).
- `Queue::workAll()` now returns `int` instead of `void`.
