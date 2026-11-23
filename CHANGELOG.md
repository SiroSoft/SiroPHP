# Changelog — SiroPHP Skeleton

## v1.1.1 (2026-11-22)

Sync release: fixes the v1.1.0 release-process gaps (version surfaces,
UPGRADE.md, docs) and applies the pending Dependabot updates. No behavior
change for application code, except the `siroJsonError()` output escaping
below (defense-in-depth, JSON shape unchanged).

### 🔧 Fixed
- `siroJsonError()` (`public/index.php`) now encodes error JSON with
  `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT` — `<`, `>`,
  `&`, `'`, `"` can no longer break out of the JSON string if a consumer
  injects the response into HTML. Same family as the core `Response::json()`
  XSS fix (core v1.4.0 P0-1).
- Version surfaces now agree: `composer.json` 1.1.1, CHANGELOG head, page
  footers (`public/index.html`, `public/index-prod.html`) and OpenAPI spec
  (`public/openapi.json` `info.version`) all report `1.1.1`, so
  `composer release:check` (version gate) passes. Rule going forward: **no
  tag until the version gate passes** — v1.1.0 was tagged while the gate
  failed on all four surfaces.
- `.env.example`: `APP_DEBUG` now defaults to `false` (was `true` — a
  copy-paste away from leaking trace/file/line in error JSON).

### 🛡️ Release engineering
- CHANGELOG backfilled with the missing `v1.1.0` entry (below) and
  `docs/RELEASE_NOTES.md` synced with it.
- `UPGRADE.md` gains the missing `v1.0.x → v1.1.0` section (throttle
  `fail_closed`, DB errors 503, error-response hardening, auth on status
  routes).
- CI gates now block: `phpcs` `continue-on-error` removed (`test.yml`),
  `composer audit` fails the build (`ci.yml`), and the `test.yml` feature
  job now also runs the Cli and EdgeCase suites. The phpcs step is scoped
  to shipped code (`app/ config/ routes/ database/ public/`, excluding
  `database/seeds/`, `--warning-severity=0`) because the repo-wide run has
  733 pre-existing errors: 719 in `tests/`, ~250 in `scripts/` + generated
  `_ide_helper.php`, and 8 false positives in `database/seeds/` (seeders
  are global-namespace BY DESIGN — `SeedCommand` scans it). The 6 real
  errors in shipped code were fixed (4 file-docblocks moved above
  `declare(strict_types=1)`, 2 multi-line `if` parens in `routes/api.php`).
  Scoped gate passes (exit 0); tests/ style debt is a separate cleanup.
- PHPStan: `tests/` is now analysed (it was excluded, so the baseline never
  applied). The baseline was regenerated (36 stale entries → 261 real
  test-code errors, all in `tests/`, none in shipped code); new test-code
  type errors fail the build.

### 📚 Docs
- README corrected: Helm chart path `k8s/` → `deploy/helm/siro-api/`,
  `13 migrations + 2 seeders` → `16 migrations + 8 seeders`,
  `8 pre-built Services` → `9`, "Zero dependencies" → "One runtime
  dependency (sirosoft/core)", `95 CLI commands` → `96` (counted with
  `php siro list --raw`).
- `docs/KNOWN_ISSUES.md` updated from v0.35 to v1.1.1 with entries
  re-verified against core v1.3.1 (including: `DB_FOREIGN_KEYS` in
  `.env.example` is not consumed by core v1.3.1 — see the file).

### 📦 Dependencies
- `sirosoft/core`: lock `v1.3.0` → `v1.3.1` (Dependabot PR, now matching
  the SiroERP install). Constraint stays `^1.1.0`: the pin to `^1.4.0`
  waits for the core v1.4.0 tag — and core must also bump its
  `Console::VERSION` const (still `'1.3.0'` in the v1.3.1 package), or the
  release-check version gate would fail.
- `phpstan/phpstan`: lock `2.2.13` → `2.3.7` (Dependabot PR proposed 2.3.5;
  2.3.7 is the newer patch in the same `^2.1` line).
- `phpunit/phpunit`: **kept at `11.5.50`** (open item). The Dependabot
  13.4.6 bump cannot install: PHPUnit 13.4.x requires PHP `>= 8.4.1`,
  while the skeleton supports PHP `>= 8.2` and this environment runs
  8.3.6. Adopting PHPUnit 13 needs a PHP `>= 8.4.1` requirement and a CI
  matrix update — a separate decision for a future minor release.

### ✅ Verification
- Full suite (PHP 8.3.6, core v1.3.1, fresh defects cache): **763 tests,
  1 stable failure** — `AppCommandsTest::testRouteRules` (pre-existing: it
  also fails on unmodified v1.1.0 code; `route:rules` exits 0 from the CLI
  but 1 when run in-process in the test harness). The repo's "761 green"
  claim does not hold. Four RBAC mutation-test failures appear only under
  defects-first ordering (pre-existing test-isolation issue, shared DB
  state); they pass in default/fresh-cache order. See `REPORT.md` in the
  v1.1.1 work branch for the full investigation.

## v1.1.0 (2026-10-07)

Error-response hardening + throttle fail-closed default. Tagged 07/10/2026;
this entry was backfilled in v1.1.1 — the v1.1.0 release shipped without a
CHANGELOG entry (see `docs/RELEASE_NOTES.md`, which already had this section).

### 🛡️ Security / behavioral changes
- Exception handler no longer leaks internals: generic messages for 404/409/
  400/500, no stack traces or driver details in error responses
  (`app/Exceptions/Handler.php`, `routes/api.php`).
- DB connection failure now returns **503** (was 500) for correct alerting.
- Throttle backend defaults to **`fail_closed`** outside the local env, so a
  degraded rate-limit backend never silently disables abuse controls
  (`routes/api.php`); the testing env is exempt (no Redis in CI).
- Auth middleware added to the status route group (`/health`, `/health/*`).
- CORS: server header values are guarded before use.
- Requires `sirosoft/core` v1.1.0+ (throttle 503 semantics, `malformed_body`).

### 🔧 Fixed
- Auth token fields persisted via `$fillable`; redundant `created_at` writes dropped.
- `ProductSeeder` requires `user_id` (was failing on fresh seed).
- Demo seed data added (`DemoSeeder`, `OrderSeeder`, `PostSeeder`,
  `ProductSeeder`, `TagSeeder` + seed assets).

### 📦 Dependencies
- `sirosoft/core`: `^1.0.0` → `^1.1.0` (Turnstile siteverify form-encoding,
  core v1.0.11–v1.0.14 alignment).

### ✅ Verification
- **763 tests** measured on the v1.1.0 baseline, **4 pre-existing failures**
  (incl. `AppCommandsTest::testRouteRules`, which also fails on unmodified
  v1.1.0 code).

### ⚠️ Upgrade notes
- See [UPGRADE.md](UPGRADE.md) (`v1.0.x → v1.1.0`): the throttle default and
  the 503-for-DB-errors change behavior — read before upgrading production.

## v1.0.1 (2026-09-10)

Patch: every surface that prints the skeleton version now reports `1.0.1`
(engine stays `sirosoft/core 1.0.0`).

### 🔧 Fixed
- Dev Dashboard (`public/index.html`) and production page (`public/index-prod.html`)
  footers: hardcoded `v0.32.0` → `v1.0.1`.
- OpenAPI spec (`public/openapi.json`): `info.version` + health schema example
  `0.15.0` → `1.0.1` (regenerate with `php siro make:openapi --version=1.0.1 --force`).
- No breaking changes from v1.0.0.

### 🛡️ Release engineering
- `scripts/release-check.php` gains a version-consistency gate: `composer.json`
  version must match runtime core, CHANGELOG head, page footers, and OpenAPI spec.

## v1.0.0 (2026-09-07)

First stable release of the skeleton, aligned with engine `sirosoft/core` v1.0.0
(API stability promise in effect).

### ⬆️ Engine
- **`sirosoft/core` `^1.0.0`** — new `composer create-project` installs get the stable
engine out of the box, including the dogfood hardening fixes (env inline comments,
DB connection reconfigure, throttle error transparency, queue auto-registration,
`make:job` interface fix).
- No breaking changes from v0.40.0: application code written against v0.40.x works unchanged.

### ✅ Verification
- 742 tests green against engine v1.0.0 (21 skipped, environment-dependent).

---

## v0.40.0 (2026-08-21)

### 🏢 Enterprise Upgrade
- Core dependency: `sirosoft/core` `^0.35.0` → `^0.40.0`
- Enterprise-grade test suite: **742 tests, 1,126 assertions, 0 failures**
- Mutation-style coverage across all layers: Controllers, Services, Repositories, Resources, Middleware, Exceptions

### 🧪 Testing
- 9 new mutation-test suites (`tests/unit/*MutationTest.php`) covering:
  - AuthController (register/login/lockout/refresh/reset/settings/dashboard)
  - User/Product/Order/Post/Category/Tag Controllers (full CRUD + RBAC 403 paths)
  - Services: UserService, RefreshTokenService, ProductService, OrderService, PostService
  - Repositories: BaseRepository, UserRepository, RefreshTokenRepository
  - Resources: User, Product, Order, Post, Category, Tag
- Deterministic admin auth via DB role assignment (no first-user race)
- Per-suite SQLite isolation + table cleanup in `setUp()`

### 🔒 Security
- Untracked `config/deploy.json` (GitGuardian generic-key alert); `.gitignore` path corrected

### 📦 Dependencies
- `sirosoft/core`: `^0.35.0` → `^0.40.0`

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
