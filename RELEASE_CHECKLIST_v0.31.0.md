# Release v0.31.0 — PR, Marketing & Community Plan

**Date:** 2026-05-26  

---

## 1. GitHub PRs

`gh` CLI not available — create PRs manually via browser:

| Repo | From → To | URL |
|------|-----------|-----|
| **siro-core** | `release/v0.31.0` → `main` | https://github.com/SiroSoft/siro-core/pull/new/release/v0.31.0 |
| **SiroPHP** | `release/v0.31.0` → `main` | https://github.com/SiroSoft/SiroPHP/pull/new/release/v0.31.0 |

### PR Description Template

```markdown
## v0.31.0 — Workflow Continuity, Auto-Auth, Polymorphic ORM

### 🔥 Killer Feature: Auto-Auth Replay
- `replay <trace_id>` auto-refreshes expired tokens → workflow continuity
- Auto-discovery auth config từ Model, Routes, Migrations (zero config)
- `.env` override: `AUTH_ENDPOINT`, `AUTH_EMAIL_FIELD`, `AUTH_PASSWORD_FIELD`, `AUTH_TOKEN_PATH`
- 6-step auth fallback: refresh → stored → env → seeder → register → interactive
- **Security:** production auto-auth disabled, password never stored

### 🧩 New: Polymorphic Relations + Pivot Data
- `morphMany()`, `morphTo()` — comments, tags, media, likes
- `withPivot()` — extra columns in pivot tables
- `attach($id, ['qty' => 5])` — pivot data on attach
- `sync([1 => ['qty' => 5]])` — sync with pivot data
- Eager loading for both directions

### 🔧 Fixed
- Trace sorting: `rsort` → `usort` by `filemtime` ở 10 command files
- Replay header deduplication (Authorization + Content-Type)
- ProductTest + OrderTest paths and auth
- PHPStan level max — 0 errors both repos

### ✅ Audit
- PHPStan: 0 errors (siro-core + SiroPHP)
- PHPUnit: 466 tests, 0 failures
- Docs: 59 files, no orphans, .env.example updated
- Changelog updated both repos
```

---

## 2. Marketing Plan

### Week 1: Launch Posts

| Platform | Content | Target |
|----------|---------|--------|
| **Hacker News** | "SiroPHP — PHP framework with request replay debugging" | Technical founders, PHP devs |
| **Reddit r/PHP** | "I built a PHP framework with zero dependencies and request replay" | PHP community |
| **Reddit r/programming** | "Debugging workflow that Laravel can't do: auto-trace → why → replay → fix" | General devs |
| **Twitter/X** | Short demo video (30s) of `siro why` → `siro replay` | PHP devs, devtools enthusiasts |
| **Laravel News** | Guest post: "What if you could replay any request?" | Laravel community |
| **PHP Weekly** | Newsletter submission | PHP developers |

### Key Messages
1. **"Zero dependencies"** — Core framework has 0 Composer dependencies
2. **"Request replay debugging"** — Trace → Why → Replay → Fix → Verify
3. **"CLI-first, no GUI needed"** — Everything from terminal
4. **"Security-first"** — OWASP Top 10 mitigated by default
5. **"PHP 8.2+ strict"** — `declare(strict_types=1)`, PHPStan max

### Demo Script for Videos
```bash
# 1. Create CRUD in 2 seconds
php siro make:crud products
php siro make:crud orders

# 2. Run migration + seed
php siro migrate
php siro db:seed

# 3. Test API
php siro t POST /api/products --body '{"name":"Laptop","price":999}'

# 4. Debug failure
php siro why
# → Shows: Exception, SQL, Middleware, Suggested Fix

# 5. Fix → Replay → Verify
# (edit code)
php siro replay <trace_id> --force
# → Status: 200 ✅ Fixed!
```

---

## 3. Community Building

### Immediate (Week 1-2)

| Task | Details |
|------|---------|
| **Setup Discord** | Replace placeholder invite link with real server |
| **Setup Twitter/X** | Start posting daily tips #sirophp |
| **GitHub Discussions** | Enable Discussions tab on both repos |
| **Issue templates** | Already exists — refine for community use |
| **CONTRIBUTING.md** | Already exists (601 lines) — update with v0.31.0 info |

### Short-term (Week 3-4)

| Task | Details |
|------|---------|
| **Packagist** | Ensure both packages are up to date on Packagist |
| **Demo project** | Create a demo repo with example API + README |
| **Video tutorial** | 5-min "SiroPHP in 5 minutes" screencast |
| **Documentation site** | Deploy `docs/` as a static site (VitePress, Docusaurus) |

### Long-term (Month 2-3)

| Task | Details |
|------|---------|
| **Plugin system** | Allow third-party packages |
| **Laravel migration guide** | Help Laravel devs try SiroPHP |
| **Starter kits** | React, Vue, Next.js frontend templates |
| **Performance benchmarks** | Publish benchmarks vs Laravel, Symfony, Slim |

---

## 4. Metrics to Track

| Metric | Target (Month 1) |
|--------|-----------------|
| GitHub Stars | 100+ |
| Discord members | 50+ |
| Packagist downloads | 500+ |
| PRs merged | 5+ (community contributions) |
| Issues created | 20+ (engagement signal) |
