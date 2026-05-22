# Release Notes

## v0.29.2 — Package Auto-Discovery (2026-05-22)

### 🚀 Package Ecosystem
- **`composer require` = instant availability**: Siro-core `v0.29.2` now auto-discovers CLI commands and service providers from installed packages via `extra.siro` in `composer.json`
- Packages can register commands (appear in `php siro list`) and HTTP providers (register routes, bindings, etc.) without manual configuration

### 📋 Package Convention Example
```json
{
    "extra": {
        "siro": {
            "commands": {
                "my:command": {
                    "handler": "Vendor\\Package\\MyCommand",
                    "desc": "Description"
                }
            },
            "providers": [
                "Vendor\\Package\\ServiceProvider"
            ]
        }
    }
}
```

---

## v0.28.2 — Schema & Migration Enhancements (2026-05-22)

### 🏗 Migration System
- **`Blueprint::dropIndex()`, `dropUnique()`, `dropForeign()`**: Remove indexes, unique constraints, and foreign keys in ALTER TABLE
- **`Blueprint::primary()` for composite keys**: Define composite PRIMARY KEY via `$table->primary(['order_id', 'product_id'])`
- **`compileAlter()` full command support**: ALTER TABLE now handles `foreign`, `unique`, `index`, `dropIndex`, `dropForeign` in addition to `addColumn`/`dropColumn`
- **`Schema::table()`**: Now executes multiple ALTER statements (not just first)

### 🔧 Bug Fixes
- **PRIMARY KEY not compiled**: `compileCreate()` silently dropped `primary` commands — fixed (skips duplicate when column type is `id`)
- **DEFAULT false → invalid SQL**: `(string) false` produced empty string — now outputs `DEFAULT 0` / `DEFAULT 1`

### 🧪 Testing
- 28 Schema tests pass, PHPStan Level Max: 0 errors

---

## v0.28.0 — Model Enhancement (2026-05-22)

### ✨ New Model Features
- **Accessors & Mutators**: Transform attributes automatically when getting (`getNameAttribute()`) or setting (`setEmailAttribute()`)
- **Virtual Attributes (Appends)**: Abstract computed fields like `full_name`, `initials` to JSON/array serialization via `$appends` property
- **DateTime Auto-Formatting**: `datetime` and `date` casts now return formatted strings instead of DateTime objects, fixing JSON serialization errors
- **Appends Getters/Setters**: `getAppends()`, `setAppends()` for runtime manipulation

### 📚 Documentation
- Updated `docs/api/Model.md` with comprehensive Accessors, Mutators, Appends, and DateTime formatting examples

---

## v0.27.0 — Developer Experience Overhaul (2026-05-20)

### 🛡️ Security Audit Fixes
- **Env cache**: 11 sensitive keys (APP_KEY, JWT_SECRET, MAIL_PASSWORD, etc.) automatically excluded from cache
- **CORS**: `.env.siro` default restricted to localhost origins
- **IDOR**: User ID ownership checks on Order and Post controllers (index/show/update/delete)
- **XSS**: All 6 Resource transformers use `htmlspecialchars()` with ENT_QUOTES | ENT_HTML5
- **JWT validation**: Algorithm mismatch detection, no "none" bypass possible
- **Session fixation**: `Session::regenerate()` called after every login
- **Token storage**: Reset/verification tokens hashed with SHA-256 before DB insert
- **Log sanitization**: Passwords, tokens, credit cards auto-redacted

### 📚 API Reference Documentation (59 files, 9,789 lines)
- **34 API reference docs**: Container, Request, Response, Router, Middleware, Model, Validation, Encryption, Events, Queue, Mail, Storage, Session, Debug, Testing, CLI, Resource, Collection, Helpers, Pagination, Observers, SoftDeletes, Schedule, Console, FormRequest, Metrics, UploadedFile, Hash, Logger, Str, Url, Http, Config, Lang
- **13 detailed guides**: Auth, Database, Testing, Deployment, Caching, Events, FileUpload, I18N, Migration, QueueMail, Validation, APIVersioning
- **WORKFLOW.md**: Complete A-to-Z guide from install to production
- **All docs in English**, zero Vietnamese

### 🖥 CLI & Developer Experience
- **Tab completion**: `siro-completion.bash` + `siro-completion.zsh` for Bash and Zsh
- **`php siro list --raw`**: Raw command list for completion scripts
- **`php siro list --json`**: JSON format for tooling/IDE integration
- **`sd()` helper**: Siro Dump — dump variables with name (`dd()` kept as alias)
- **CLI colors**: Error in red, success in green, warning in yellow, info in blue (auto-detect terminal)
- **`_ide_helper.php`**: Full `@method` annotations for Route, DB, Cache, Event, Logger, Hash, Encrypter, Storage, Session, Str facades
- **`.phpstorm.meta.php`**: Container::make() returns correct types in PhpStorm
- **JWT error detail**: Debug mode shows specific errors (expired, bad signature, revoked, algorithm mismatch). Production keeps generic "Invalid or expired token"
- **DB connection error**: Custom exception with configuration troubleshooting hints
- **Route 404 "Did you mean?"**: Suggests similar routes when debug mode enabled
- **`make:observer`**: Generate model observer class
- **`make:request`**: Generate FormRequest class with validation rules
- **`make:rule`**: Generate custom validation rule class
- **Auto timestamps**: Model `$timestamps = true` auto-sets `created_at`/`updated_at` on save

### 📝 REST API Quality
- **Error format**: Errors moved from `meta.errors` to top-level `errors` — standard REST API convention
- **`.env.example`**: Template shipped with project
- **`favicon.ico` + `robots.txt`**: Routes prevent 404 noise from browser requests

### 📦 Export (OpenAPI + Postman)
- **operationId**: All 27 endpoints have auto-generated operationIds (`productList`, `authLogin`, ...)
- **Dynamic descriptions**: Response descriptions per resource ("Products list", "User created", "Password reset")
- **Path param descriptions**: Each path parameter has a description ("Product ID")
- **PATCH optional**: PATCH request fields are all optional (not required)
- **Response examples**: Example values auto-generated from Resource transformers
- **Postman folders**: Collection grouped into 8 folders (Auth, Product, Order, User, Category, Tag, Post, General)
- **Postman variable syntax**: `{{id}}` instead of `:id` — correct Postman format
- **Postman response examples**: Response body auto-generated from Resource files
- **Public copy**: `public/openapi.json` and `public/postman_collection.json` automatically updated

### 🐛 Bug Fixes
- **Pagination**: Missing docs for built-in pagination
- **PostService**: `user_id` field dropped during post creation (IDOR store fix)
- **Migration compatibility**: `user_id` column added to base orders/posts table migrations (not just ALTER TABLE)
- **PHPStan level max**: All 6 new errors from OpenAPI command fixed

## v0.28.1 — Migration & QueryBuilder Enhancements (2026-05-19)

### 🏗 Migration System
- **`migrate:fresh`**: Drop all tables + re-migrate with `--seed` option
- **`migrate:status --pending`**: Show only pending migrations
- **`foreignId()`** helper added to Blueprint
- File naming standardized to `Y_m_d_His`

### 🔧 QueryBuilder
- **`groupByRaw()`** / **`havingRaw()`**: Raw SQL functions in GROUP BY, HAVING
- **`DB::raw()`**: Raw expression facade for any clause

### 🖥 CLI
- **`registerCommand()`** / **`registerCommands()`**: Register custom commands from app code
- **`env:check`**: MySQL version check (> 8.0 for JSON column support)
- **`log:trace`**: 4 new filters — `--ip`, `--path`, `--error`, `--since`

### 🧰 Other
- **`Response::raw()`**: Auto-detect Content-Type (JSON, HTML)
- 19,496 tests passing (19,034 core + 462 skeleton), 0 failures
- PHPStan level max: 0 errors

---

## v0.28.0 — Comprehensive Security Audit (2026-05-19)

### 🛡️ Security Hardening
- 7 CRITICAL fixes: RCE, SMTP injection, Code injection, SQL compat, HMAC, Listeners, Tests
- 8 HIGH fixes: SSRF, MITM, Rate limit bypass, JTI blacklist, Redis cleanup, LOCK_EX
- **Env system**: 5-tier priority chain (`.env.siro` → `.env` → `.env.{env}` → `.env.local` → `.env.{env}.local`)
- **Log Replay**: `--edit`, `--diff`, `--set`, `--dry-run` modes
- 42/42 penetration tests passed (OWASP Top 10 full coverage)
- PHPStan level max: 0 errors | 19,496 tests — 0 failures

---

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
