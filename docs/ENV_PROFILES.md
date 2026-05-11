# Environment Profiles

This project includes ready-to-fill templates for secure deployments:

- `.env.example` for local development
- `.env.staging.example` for staging
- `.env.production.example` for production

## Quick Setup

### Staging

1. Copy `.env.staging.example` to `.env`
2. Replace all `replace_with_*` placeholders
3. Configure staging domains in `CORS_ALLOWED_ORIGINS`
4. Run `php siro doctor --prod` and `php siro test`

### Production

1. Copy `.env.production.example` to `.env`
2. Replace all `replace_with_*` placeholders
3. Ensure `APP_ENV=production` and `APP_DEBUG=false`
4. Set strict CORS domains (no wildcard)
5. Run `php siro doctor --prod`, `php siro test`, and PHPStan

## Required Secret Rotation Policy

- Rotate `APP_KEY` and `JWT_SECRET` for each environment
- Never reuse staging secrets in production
- Never commit real secrets to Git

## Minimum Verification Before Release

- `php siro doctor --prod`
- `php siro test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `composer audit --no-interaction`

For full release gates, use `docs/SECURITY_RELEASE_CHECKLIST.md`.
