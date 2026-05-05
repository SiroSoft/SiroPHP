# Changelog

## v0.15.0 (2026-05-06)

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
