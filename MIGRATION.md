# Migration Guide

**No breaking changes.** All v0.x versions are backward compatible.

---

## v0.22 → v0.23 (Current)

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
