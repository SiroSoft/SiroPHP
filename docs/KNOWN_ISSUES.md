---
title: K NO WN I SS UE S
description: SiroPHP K NO WN I SS UE S reference
sidebar_position: 3
sidebar_label: K NO WN I SS UE S
---

# Known Issues & Limitations

## v1.2.0 Known Issues (verified 2026-11-27 against `sirosoft/core` v1.5.0)

> Entries below were re-checked against the v1.2.0 tree. Items that could
> not be verified are marked as such — do not treat this file as a promise
> that an item is fixed just because its version heading moved.

### Database

| Issue | Severity | Workaround |
|-------|----------|------------|
| No transaction rollback in CLI | Low | Use `Database::transaction()` manually (`Siro\Core\Database::transaction()`) |
| SQLite foreign keys off by default | Medium | Issue `PRAGMA foreign_keys = ON` per connection. Note: `.env.example` ships `DB_FOREIGN_KEYS=ON`, but core v1.5.0 does not read that variable — the setting alone does not enable enforcement (core gap, to be raised upstream) |
| Rollback is by step count only, not by named migration | Low | Use `migrate:rollback --step=N` (reverts the last N batches) |

### Auth

| Issue | Severity | Workaround |
|-------|----------|------------|
| No OAuth2 wired into the skeleton by default | Low | Core v1.5.0 ships an optional OAuth2 bridge (`league/oauth2-server`: authorization_code + PKCE, client_credentials, refresh_token). Enable it with `config/oauth.php` + `php siro make:oauth-keys` + `php siro make:oauth-client`; see core `docs/OAUTH2.md`. |
| No multi-tenancy | Medium | Implement in application layer |
| No 2FA built-in | Low | Add manually or use third-party |

### File Upload / Storage

| Issue | Severity | Workaround |
|-------|----------|------------|
| No chunked upload | Medium | Handle in frontend |
| S3 driver basic (no multipart) | Low | Use local storage for files >100MB |

### Testing

| Issue | Severity | Workaround |
|-------|----------|------------|
| RBAC mutation tests failed in defects-first order | — | **Fixed in v1.2.0.** Root cause: the `*ForbiddenForNonAdmin` tests registered one user and assumed it was a plain user, but `UserService` makes the first registered user in a (per-class) DB an admin — so in defects-first order the "non-admin" user WAS the admin (mutations returned 200/201 instead of 403) and hardcoded resource id 1 did not exist yet (404 instead of 403). The `*ForbiddenForOtherUser` user tests had the mirror fault: hardcoded id 1 was the test's own user when it ran first (self-access → 200). Fix: new `TestCase::registerUser()` helper forces the role via SQL after registration and every forbidden test now seeds its own target resource and uses the real returned id. Verified: full suite green in default order AND in defects-first order (2 consecutive runs, 763 tests, 0 failures). |
| `testRouteRules` failed on core v1.4.0, fixed by core v1.5.0 | — | No action. `route:rules` now exits 0. |
| `UploadE2eTest` fails on a fresh checkout | Low | Run `php siro migrate` first (CI does this). The E2E server boots against `storage/test.db`; on a fresh checkout the file is empty and register returns 500 (`no such table: users`). |
| `UploadE2eTest` leaves an orphan `php -S :8099` server | Low | Kill it by PID (`ss -tlnp \| grep 8099`, then `kill -9 <pid>`). `tearDownAfterClass`'s `proc_terminate` does not reliably stop the server, so the next run skips both E2E tests ("port busy"). |

### Queue/Jobs

| Issue | Severity | Workaround |
|-------|----------|------------|
| Queue: DB driver (default), Redis driver (QUEUE_DRIVER=redis) | Low | Use database queue with retries |
| No job retry UI | Low | Check `failed_jobs` table manually |

---

## Architecture Limitations

By design, not bugs:

| Limitation | Why | Workaround |
|------------|-----|------------|
| No admin panel | API-only | Build with any frontend |
| No native WebSocket (Mercure/SSE available via FrankenPHP) | HTTP-only | Use polling or Pusher |
| No GraphQL | REST-first | OpenAPI covers most needs |
| No web debug bar | CLI-first | `log:trace`, `replay`, `why` |

---

## Reporting Issues

1. https://github.com/SiroSoft/SiroPHP/issues
2. Include: PHP version, Siro version, reproduction steps
