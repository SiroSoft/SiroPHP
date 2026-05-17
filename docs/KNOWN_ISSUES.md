# Known Issues & Limitations

## v0.23 Known Issues

### Database

| Issue | Severity | Workaround |
|-------|----------|------------|
| No transaction rollback in CLI | Low | Use `DB::transaction()` manually |
| SQLite foreign keys off by default | Medium | Enable with `PRAGMA foreign_keys = ON` |
| No migrations rollback (only reset) | Low | `migrate:fresh` drops and recreates |

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
| File-based only (no Redis) | Medium | Use database queue |
| No job retry UI | Low | Check `failed_jobs` table manually |

---

## Architecture Limitations

By design, not bugs:

| Limitation | Why | Workaround |
|------------|-----|------------|
| No admin panel | API-only | Build with any frontend |
| No WebSocket | HTTP-only | Use polling or Pusher |
| No GraphQL | REST-first | OpenAPI covers most needs |
| No web debug bar | CLI-first | `log:trace`, `replay`, `why` |

---

## Reporting Issues

1. https://github.com/SiroSoft/SiroPHP/issues
2. Include: PHP version, Siro version, reproduction steps
