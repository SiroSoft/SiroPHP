# Changelog

## v0.21.0 (2026-05-10) — Security & Quality Release

### 🐛 Bug Fixes
- **CRITICAL**: Rotated exposed JWT_SECRET in .env — old key was committed to repo
- **FIXED**: UploadedFile method calls in routes — `originalName()` → `getClientOriginalName()`, `size()` → `getSize()`, `mime()` → `getMimeType()`
- **FIXED**: Lang::count() removed — replaced with `count(Lang::get())` in routes
- **FIXED**: Dockerfile — `key:generate` moved from build-time to runtime CMD
- **FIXED**: CategoryController/TagController update rules — removed `required` (PATCH semantics)
- **FIXED**: Hardcoded admin credentials in UserSeeder — now reads from env
- **FIXED**: Missing `declare(strict_types=1)` in AuthController, CategoryController, HomeController
- **FIXED**: Env cache excluded JWT_SECRET — added fallback loading from .env
- **FIXED**: Log sanitization — enabled LOG_SANITIZE_* in .env.example and loading in index.php
- **FIXED**: phpunit.xml — failOnRisky/warning now true

### 🔧 Improvements
- CORS_ALLOWED_ORIGINS restricted to localhost in .env
- APP_DEBUG=false by default
- Added translation files (en + vi) for validation and messages
- Deleted dead code: HomeController.php (unused), SendWelcomeEmailJob.php (duplicate)
- Translation files are now bundled (storage/lang/en, storage/lang/vi)

### 🔧 Improvements
- MiddlewareInterface implemented in all skeleton middleware (Auth, Cors, Json, Throttle, SecurityHeaders)
- CategoryController now extends `Siro\Core\Controller` base class
- PHPStan raised to Level 7 (skeleton: 0 errors, baseline 142)
- Docker: non-root user, HEALTHCHECK, supervisor config
- Route coverage: Tags, Orders, Posts CRUD tests added

### 📊 Testing
- 414 tests, 549 assertions — all passing
- Tags/Orders/Posts endpoint tests: index, show, store validation (10 tests)
- Categories full CRUD coverage maintained

## v0.20.0 (2026-05-09) — Production-Ready Release

### 🚀 New Features
- **HTML Homepage** - Beautiful landing page at root path for browser users
- **Content Negotiation** - Automatic HTML/JSON response based on client type
- **Apache Support** - .htaccess configuration for proper routing
- **Security Enhancements** - SecurityTest suite integration from siro-core

### 🔧 Improvements
- Version synchronization to v0.20.0 across all files
- Updated README with homepage access instructions
- Enhanced API routes with proper version references
- Improved HomeController with strict_types declaration

### 📊 Testing
- Integrated SecurityTest suite (30+ tests)
- BenchmarkCommand available via siro-core dependency
- Maintained 57 application tests

### 🐛 Bug Fixes
- Fixed missing `declare(strict_types=1)` in controllers
- Fixed API response version numbers
- Fixed Lang::count() deprecation with safer alternative
- Fixed UploadedFile method calls to use standard Symfony methods

## v0.16.7 (2026-05-09) — API Reliability & Performance Release

### 🔗 Model Relations
- **HasOne relation** — `Model::hasOne()` for one-to-one relationships
- **BelongsToMany relation** — `Model::belongsToMany()` with attach/detach/sync/has/toggle methods for many-to-many

### 📁 File Upload Helpers
- **UploadedFile validation** — `isImage()`, `isPdf()`, `hash()`, `maxSize()`
- **Request::validateFile()` — Chainable file validation
- **Response::downloadFromStorage()` — Secure file downloads
- **Storage helpers** — `localPath()`, `putFile()`, `copy()`, `size()`, `lastModified()`

### 🔒 API Reliability
- **Idempotency Keys** — Prevent duplicate requests with `Idempotency-Key` header
  - TTL-based response caching
  - CLI: `make:idempotency-table`
- **API Key Auth** — Simple authentication for external developers
  - Scopes: read/write/admin
  - CLI: `make:apikey`
  - Middleware for route protection

### ⚡ Performance Optimizations
- **Batch Operations** — `QueryBuilder::updateWhereIn()`, `deleteWhereIn()`, `insertMany()`
- **Cursor Pagination** — `QueryBuilder::cursorPaginate()` — stable under concurrent inserts
- **Lazy DB Connection** — DB connects only on first query, not at boot (~35-40% faster)
- **Config Caching** — Config files cached, auto-reload on file change
- **Route Caching** — Routes pre-compiled and cached (`php siro optimize`)
- **Opcache Preloading** — `preload.php` for 10-20% startup improvement
- **New CLI** — `config:clear` to clear all caches

### 🧪 Testing & Quality
- **siro-core**: 657 tests, 1977 assertions, 33 skipped
- **SiroPHP**: 215 tests, 336 assertions, all pass
- **PHPStan Level 6**: Both pass (zero errors)

---

## v0.15.1 (2026-05-06) — Security & Stability Release

### 🛡️ Security Hardening
- **JWT protection** — Algorithm confusion prevention, null token rejection
- **XSS prevention** — Output escaping via `htmlspecialchars()` on all resources
- **SQL injection** — Confirmed prepared statements throughout, array type rejection
- **Null byte injection** — Stripped from URL parameters
- **Type confusion** — Arrays rejected for string validation rules
- **Whitespace-only bypass** — Trim before `min`/`max`/`required` checks
- **Path traversal** — Normalized path blocks `../` access
- **Alg=none attack** — Unsupported algorithm rejection

### 🔧 Bug Fixes (27 issues)
- Password confirmation validation (`|confirmed` rule)
- DELETE operations return HTTP 204 instead of 200
- Registration/login response flat structure (`access_token`)
- Categories no longer require non-existent `slug` field
- Header key case-insensitivity (`Authorization` vs `authorization`)
- Validation errors at root level for consistent API responses
- `select()` now supports variadic arguments (`->select('id','name')`)
- Added `insertGetId()` and `selectRaw()` to QueryBuilder
- Added `onlyTrashed()` alias for Laravel compatibility
- Login email auto-trimmed via `Request::string()`
- `app.log` auto-created on boot
- Rate limit files cleaned between tests

### 🧪 Testing & Quality
- **642 tests — 100% pass** (509 official + 133 edge/dark/real-user)
- **Transaction isolation** — Each test auto-rolls back database changes
- **33 real-user flow scenarios** — Registration → login → CRUD → pagination → rate limiting
- **52 edge cases** — Unicode, emoji, extreme values, HTTP methods
- **40 dark-side attacks** — JWT tampering, SQL injection, XSS, path traversal, type confusion
- **Auto table creation** — Migrations run automatically in test setup

### 📋 Audit
- Full security audit documented in `SECURITY_AUDIT.md`
- 37 attack vectors tested across 8 categories
- Zero exploitable vulnerabilities found

### 🚀 New Features

- **Schema Builder** — Driver-agnostic migrations, write once run on MySQL/PostgreSQL/SQLite
- **Multi-DB Connections** — `Database::connection('analytics')`, `DB::table('x')->connection('replica')`
- **AES-256 Encryption** — `Encrypt::encrypt()`/`decrypt()` with HMAC integrity
- **HTTP Client** — `Http::get()`/`post()` — zero-dependency curl wrapper
- **Maintenance Mode** — `php siro down --message="..."`, `php siro up`
- **Foreign Key Constraints** — In Schema Builder
- **Health Endpoint** — `GET /health` with DB status, version, timestamp
- **DatabaseSeeder** — Ordered seeder orchestration with `$calls` array

### 🛡️ Production Security

- **Log Sanitization** — Passwords, tokens, credit cards, OTPs auto `[REDACTED]` in traces
- **Replay Production Lock** — `--dry-run` only in production
- **Audit Trail** — Every replay logged
- **Log Protection** — `.htaccess` auto-generated
- **OpenAPI Production Lock** — Disabled by default

### 💻 CLI & Developer Experience

- **59 commands** — Layered UX (core workflow → daily dev → advanced → system)
- **`php siro start`** — Quick onboarding
- **`php siro t`** — Short alias for `api:test`
- **`php siro fix`** — Watch + auto-replay
- **`php siro why`** — Debug last request
- **`php siro replay`** — Replay with `--edit`/`--diff`
- **`php siro traces`** — Browse recent traces

### 🧪 Testing & Quality

- **197 tests, 275 assertions** — All pass
- **HTTP test helpers** — `$this->get('/')->assertOk()->assertJson([...])`
- **Database assertions** — `$this->assertDatabaseHas('users', ['email'=>'...'])`
- **Health endpoint tests** — 6 tests
- **Test helper tests** — 13 tests covering assertStatus, assertJson, assertDatabaseHas, auth flow, CRUD

### 📖 API Documentation

- **Dynamic OpenAPI** — Reads actual routes, controllers, validation rules
- **30 endpoints, 34 schemas, 6 tags**
- **Swagger UI** at `/docs.html`
- **Postman collection** with auto-login pre-request

### 🔧 Fixes

- Routes version updated `0.13.0` → `0.14.1`
- PostgreSQL port auto-detection (5432)
- Driver-aware default ports in database config
- `.env.example` with PostgreSQL + security config

---

## v0.14.1 (2026-05-05)

- Service & Repository layers
- `make:service`, `make:repository`
- PHPUnit test generation with `make:test`
- `make:crud` with full layers
- README marketing revamp

## v0.14.0 (2026-05-04)

- `debug:last`, `log:top`, `route:search`, `doctor --prod`, `api:test --loop`

## v0.13.0 (2026-05-03)

- Factory generator, `db:show`, `route:rules`, live reload, deploy system

## v0.12.0 (2026-05-02)

- `make:crud` scaffolding, `make:test`, benchmarks, `env:switch`

## v0.11.0 (2026-04-30)

- Service & Repository, eager loading, PHP 8.4

## v0.10.0 (2026-04-29)

- Rate limiter, CSRF, config caching, optimize

## v0.9.0 (2026-04-28)

- Queue, mail, events, scheduler, multi-language

## v0.8.0 (2026-04-27)

- Debugging system (trace ID, replay, export), Swagger UI, Postman

## v0.7.0 (2026-04-26)

- Initial release
