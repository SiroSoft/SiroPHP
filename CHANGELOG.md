# Changelog

## v0.24.0 (2026-05-13) — Security Hardening, Architecture Fixes, Docker Ready

### 🛡️ Security Fixes
- **AuthController refactored** — now uses `UserService` pattern (getByEmail, createUser, verifyEmail, etc.)
- **Duplicate middleware consolidated** — `ThrottleMiddleware`, `CorsMiddleware` removed from `app/Middleware/`, use core versions
- **Middleware alias conflict fixed** — `App::boot()` no longer overwrites app-level aliases
- **CORS middleware route reference fixed** — `routes/api.php` uses `Siro\Core\Middleware\CorsMiddleware`
- **Default JWT_SECRET extended** — Docker default 48 chars (was 24, violated min 32-char policy)

### 🏗️ Architecture
- **AuthController** — all auth methods (register, login, refresh, verifyEmail, forgotPassword, resetPassword) refactored to delegate to `UserService`
- **UserService** — new static methods: `getByEmail()`, `createUser()`, `getTokenVersion()`, `verifyEmail()`, `initiatePasswordReset()`, `resetPassword()`
- **BaseService** — converted from abstract class to interface
- **`config/app.php`** — `APP_URL` fixed to use `Env::get()` instead of dead `defined()` check

### 🐛 Bug Fixes
- `bool > 0` type-unsafe comparison in `UserService::incrementTokenVersion()` fixed
- `routes/api.php` — `CorsMiddleware` namespace updated (was referencing deleted app middleware)

### 📦 Infrastructure
- **Dockerfile**: Production-ready Docker image with PHP 8.2 CLI Alpine
  - `composer install --no-dev`, `php siro key:generate`, `php siro config:cache`
  - Exposes port 8080
- **Dockerfile.dev**: Development Docker image with live composer install
- **docker-compose.yml**: Updated to use proper Dockerfiles, fixed JWT_SECRET default

### 🧪 Testing
- `AuthServiceIntegrationTest` — 6 tests verifying AuthController delegates to UserService
- All 7 UserService static methods verified

### 📦 Dependencies
- `sirosoft/core` bumped to `^0.24.0`
- PHP >= 8.2, ext-pdo, ext-json, ext-mbstring

## v0.23.0 (2026-05-12) — API Versioning, ETag, Metrics, Auth Caching

### 🆕 New Features
- **API Versioning**: `version` middleware on `/api` group — header-based version negotiation
  - Client: `Accept: application/vnd.siro.v2+json`
  - Response: `X-API-Version: 1` header
- **ETag / Conditional Requests**: `etag` middleware — auto 304 Not Modified for cached responses
- **Prometheus Metrics**: `/metrics` endpoint in OpenMetrics format
  - Auto-track request count, duration histogram, status codes

### ⚡ Performance
- **AuthMiddleware**: Request-scoped user cache — `User::find()` called once per request instead of every middleware
- Updated to `sirosoft/core ^0.23`

### 🧪 Testing
- **426 tests** passing — 0 failures
- Updated route integration tests for new middleware chain

### 🔧 Config
- phpunit.xml: coverage report (HTML, Clover, text)
- Routes: `/health/ready`, `/metrics` unauthenticated endpoints
