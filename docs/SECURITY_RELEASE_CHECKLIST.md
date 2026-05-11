# Security Release Checklist

Use this checklist before shipping to production.

## 1) Environment

- `APP_ENV=production`
- `APP_DEBUG=false`
- Strong `APP_KEY` and `JWT_SECRET`
- No placeholder secrets in any env file
- `CORS_ALLOWED_ORIGINS` is explicit (no `*`)

## 2) HTTP / Network

- HTTPS enabled at load balancer or web server
- HSTS enabled in production responses
- `storage/logs` denied from public access
- Trusted proxy config matches your infra (`APP_TRUSTED_PROXIES`)

## 3) Auth / Access Control

- All write endpoints (`POST/PUT/DELETE`) require auth middleware
- Admin-only actions also enforce role checks
- Refresh token rotation active and revoke flow verified

## 4) Data / Storage

- Backups configured for DB and storage
- Migrations reviewed and applied in staging before production
- Upload validation policy tested (type, size, path traversal)

## 5) Rate Limiting / Abuse Protection

- Throttle middleware enabled on auth and write routes
- Production fallback policy reviewed (`THROTTLE_FALLBACK`)
- Alerts for repeated 401/403/429 responses configured

## 6) Verification Gates

- `php siro doctor --prod` passes except environment-specific HTTPS probe when expected
- `php siro test` passes
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G` passes
- `composer audit --no-interaction` has no known vulnerabilities

## 7) Rollout

- Release notes include security-impacting changes
- Rollback plan documented and tested
- Post-deploy smoke tests for `/health`, login, and one protected write endpoint
