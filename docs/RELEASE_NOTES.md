# Release Notes

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
