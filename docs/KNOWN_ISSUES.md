---
title: K NO WN I SS UE S
description: SiroPHP K NO WN I SS UE S reference
sidebar_position: 3
sidebar_label: K NO WN I SS UE S
---

# Known Issues & Limitations

## v1.1.1 Known Issues (verified 2026-11-22 against `sirosoft/core` v1.3.0)

> Entries below were re-checked against the v1.1.1 tree. Items that could
> not be verified are marked as such — do not treat this file as a promise
> that an item is fixed just because its version heading moved.

### Database

| Issue | Severity | Workaround |
|-------|----------|------------|
| No transaction rollback in CLI | Low | Use `Database::transaction()` manually (`Siro\Core\Database::transaction()`) |
| SQLite foreign keys off by default | Medium | Issue `PRAGMA foreign_keys = ON` per connection. Note: `.env.example` ships `DB_FOREIGN_KEYS=ON`, but core v1.3.0 does not read that variable — the setting alone does not enable enforcement (core gap, to be raised upstream) |
| Rollback is by step count only, not by named migration | Low | Use `migrate:rollback --step=N` (reverts the last N batches) |

### Auth

| Issue | Severity | Workaround |
|-------|----------|------------|
| No OAuth2/Passport support | Low | Use API Key auth for external devs |
| No multi-tenancy | Medium | Implement in application layer |
| No 2FA built-in | Low | Add manually or use third-party |

### File Upload / Storage

| Issue | Severity | Workaround |
|-------|----------|------------|
| No chunked upload | Medium | Handle in frontend |
| S3 driver basic (no multipart) | Low | Use local storage for files >100MB |

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
