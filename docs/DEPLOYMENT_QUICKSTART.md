# Deployment Quickstart

This guide gives a minimal, safe path from local to staging/production.

## 1) Choose the right env template

- Local: `.env.example`
- Staging: `.env.staging.example`
- Production: `.env.production.example`

Copy one template to `.env` and replace all `replace_with_*` values.

## 2) Run preflight checks

```bash
composer release:check
php siro doctor --prod
php siro test
php vendor/bin/phpstan analyse --no-progress --memory-limit=1G
composer audit --no-interaction
```

Expected result: tests/statics/audit pass; doctor only fails for environment items you intentionally have not enabled yet (for example HTTPS in local CLI).

## 3) Verify security-sensitive behaviors

```bash
# should be 401 without token
php siro api:test POST /api/products name=Smoke price=10

# health endpoint should be 200
php siro api:test GET /health
```

## 4) Web server checks

- Ensure HTTPS terminates correctly (LB/Nginx/Apache)
- Ensure `storage/logs` is not public
- Keep `public/nginx-log-protection.conf` included in server config

## 5) Final release gate

Before deploy, walk through:

- `docs/ENV_PROFILES.md`
- `docs/SECURITY_RELEASE_CHECKLIST.md`
