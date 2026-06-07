# Changelog — SiroPHP Skeleton

## v0.35.0 (2026-06-07)

### 🚀 Features
- Redis queue driver (`QUEUE_DRIVER=redis`) for high-throughput background job processing
- Mercure/WebSocket integration: publish Server-Sent Events from PHP, auto-publish on Model create/update
- Mercure CLI: `php siro mercure:subscribe <topic>` for terminal-based topic subscription
- Redis rate limiter driver for high-traffic production deployments
- Email verification flow with token-based confirmation and resend
- Demo workflow mode for quick prototyping and client presentations

### 🔧 Debug & CLI
- Enhanced trace filtering with additional query and error filters
- Replay diff highlighting for clearer before/after comparisons
- Structured error output with machine-parseable JSON fallback

### 🧪 Testing
- 463 tests, 0 errors, 0 failures
- PHPStan level max: 0 errors

## v0.34.0 (2026-06-03)

### 🛡️ Security
- `Handler.php`: `DuplicateEmailException` → 409 Conflict, `NoFieldsToUpdateException` → 400 Bad Request (were 500)
- `AuthController`: bcrypt cost unified to 10
- `SecurityHeadersMiddleware`: CSP enhanced with `object-src 'none'`, `base-uri 'self'`
- Debug scripts (`debug_trace.php`, `debug_upload.php`): removed from production

### 🐛 Bug Fixes
- **Test DB migration recording**: failed migrations no longer recorded (was causing "no such table" errors)
- **Test schema**: `users` now includes `updated_at`, `soft_delete_models` includes `created_at` + `updated_at`
- **ProductTest**: authenticate once per class, stale DB file cleanup
- PSR-12 code style fixes across all PHP files (CRLF → LF, inline control structures)

### 📦 Dependencies
- `phpunit/phpunit`: `11.5.50` → `^11.5.55`
- `sirosoft/core`: `^0.33.1` → `^0.34.0`

### 🧪 Testing
- 463 tests, 0 errors, 0 failures (19 skipped)
- PHPStan level max: 0 errors
- All 30 "no such table" errors fixed (migration recording logic)
- All 65 "Failed to login" 401 errors fixed (stale PDO prepared statements + Schema PDO cache)

### 🔧 CLI
- `siro` help now shows correct default ports for `serve` (8080) and `live` (9090)
