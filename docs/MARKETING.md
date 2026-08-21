# SiroPHP — Marketing Playbook

> Working copy for landing page, launch announcements, and docs hero.
> Last updated: 2026-08-01

---

## 1. Positioning Statement

> **SiroPHP** — a zero-dependency, high-speed PHP API framework with a **replay-first
> debug workflow** no other framework has: *run → see the trace → replay the exact
> request → fix → auto re-run* in seconds — plus a **tamper-evident audit trail**
> built in. No Composer bloat. No config ceremony. Just `php siro` and you're debugging
> production like it's local.

---

## 2. The Three Pillars

These are NOT "me too" features. They are the reasons someone switches.

| Pillar | Strong claim | Proof in the product |
|---|---|---|
| **Replay / Debug workflow** | "Bugs are history — replay the exact request, inspect middleware + SQL + timing, fix, and auto re-run." | `php siro why`, `replay --diff`, `api:why`, `db:why`, `log:tail`, `fix` |
| **Zero dependency** | "The entire framework is pure PHP + SQLite/PDO. No package bloat, no auditing a hundred vendored deps." | `composer.json` requires only `sirosoft/core` |
| **Immutable audit trail** | "Every sensitive action is HMAC-chained — any edit or deletion of a log entry is detected. SOC2-friendly out of the box." | `audit:verify`, `audit:log`, `Audit` middleware |

---

## 3. Taglines (A/B test these)

1. **Ship fast. Debug faster.**
2. **The PHP framework that replays your bugs.**
3. **Zero-dependency PHP. Trace. Replay. Fix.**
4. **Your bugs, replayed in 2 seconds.**

Recommended default: **"Ship fast. Debug faster."**

---

## 4. Comparison Table (for landing page)

| | SiroPHP | Laravel | Slim |
|---|---|---|---|
| Replay a production request from a trace | ✅ built-in | ❌ | ❌ |
| Zero dependency (pure PHP) | ✅ | ❌ | ⚠️ partial |
| Tamper-evident audit trail | ✅ built-in | ❌ (needs a package) | ❌ |
| Boot time (CLI) | ~87ms | ~150–400ms | ~50ms |
| Warm request | ~28ms | ~50–100ms | — |
| Debug CLI workflow (`why` / `fix` / `replay`) | ✅ | ❌ | ❌ |
| 19k+ tests, PHPStan level max | ✅ | ✅ | — |

---

## 5. Audience & Use Cases

- **Independent devs / small startups** building fast APIs with zero friction.
- **Teams maintaining legacy APIs** that need a "why did this fail" tool that actually works.
- **Security-conscious projects** that need an audit trail with near-zero setup.
- **Tutorial/demo-friendly** — the whole workflow fits in 5 commands.

---

## 6. Call To Action (README / docs hero)

```bash
php siro make:crud products     # CRUD API in 2 seconds
php siro serve                  # run it
php siro t GET /api/products    # test it
# ... it fails?
php siro why                    # why did the last request fail?
php siro fix                    # fix code, it auto re-runs the test
php siro audit:verify           # audit trail intact? one command
```

---

## 7. Launch Story / Narrative

> Most frameworks help you *write* code. SiroPHP is built around helping you
> *understand what happened*. When a request fails in production, you don't grep
> log files — you replay the exact request, diff the response before/after your fix,
> and let the watcher re-run it the moment you save. It's the debugging workflow
> every developer wishes their framework shipped with.
>
> And because the whole thing is pure PHP with zero dependencies, it boots in
> milliseconds and audits itself — every security-relevant action is written to a
> tamper-evident HMAC chain you can verify with one command.

---

## 8. Feature One-Liners (for changelogs / tweets)

- `php siro make:crud` → full CRUD (model, migration, controller, resource, test, routes) in one command.
- `php siro why` → "why did the last request fail?" with middleware pipeline + timing.
- `php siro replay --diff` → replay a trace, show response before vs after your fix.
- `php siro db:why --query=...` → EXPLAIN + index suggestions for a slow query.
- `php siro audit:verify` → prove the audit trail has not been tampered with.
- `php siro live` → dev server that restarts itself on file change (and doesn't kill your other PHP processes).

---

## 9. What We Are NOT (avoid positioning traps)

- ❌ NOT a Laravel replacement — don't pitch "Laravel killer".
- ❌ NOT a full-stack CMS framework (no Blade/templating, no ORM catalog like Eloquent).
- ❌ NOT competing on package ecosystem.
- ✅ We compete on: **debug velocity, zero-dependency trust, and audit security**.
