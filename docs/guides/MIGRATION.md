---
title: M IG RA TI ON
description: SiroPHP M IG RA TI ON reference
sidebar_position: 9
sidebar_label: M IG RA TI ON
---

# Migration Guide

**No breaking changes.** All v0.x versions are backward compatible.

---

## v0.34 → v0.35.0 (Current)

```bash
composer update sirosoft/core:^0.35.0
```

### Breaking Changes
- Database::connection() returns PDO directly, use DB::table() for query builder

### New (opt-in)
- `Redis queue driver` — set `QUEUE_DRIVER=redis`
- `Mercure/WebSocket integration` — real-time SSE via Mercure hub
- `Rate limiter Redis driver` — shared Redis connection via CacheInstance
- `Email verification flow` — `POST /api/auth/verify-email/resend`
- `Validation nesting` — `items.*.product_id` syntax
- `Debug workflow demo` — `php siro demo`

---

## v0.22 → v0.23

```bash
composer update sirosoft/core:^0.23
```

### New (opt-in)
- **API Versioning**: middleware `version` added to `/api` group
- **ETag**: middleware `etag` auto-returns `304 Not Modified`
- **Metrics**: GET `/metrics` endpoint (OpenMetrics format)
- **Auth caching**: User DB query cached per request

---

## v0.16 → v0.22

- DELETE returns 204 (not 200)
- Headers are case-insensitive
- New CLI: `api:test`, `replay`, `why`, `log:trace`

---

## Upgrading

```bash
composer update sirosoft/core
php vendor/bin/phpunit
php siro config:clear
```
