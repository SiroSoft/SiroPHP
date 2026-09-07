---
title: CLI
description: SiroPHP CLI Command Reference
sidebar_position: 1
sidebar_label: CLI
---

# CLI Command Reference

## Overview

Siro ships with **93 CLI commands**. Every task — from project creation to production debugging — is done from the terminal. No GUI tools needed.

```bash
php siro                    # Core workflow overview
php siro list               # All commands grouped
php siro list --raw         # Raw command list (for tab completion)
php siro list --json        # JSON format (for tooling)
php siro <cmd> --help       # Details + options
php siro --version          # Show version
```

## Tab Completion

Type faster. Press Tab to autocomplete commands.

### Bash

```bash
# Add to ~/.bashrc
source /path/to/siro-completion.bash
```

### Zsh

```bash
# Add to ~/.zshrc
source /path/to/siro-completion.zsh
```

### How it works

```bash
php siro mak + Tab → php siro make:crud
php siro mig + Tab → php siro migrate
php siro log: + Tab → log:tail, log:trace, log:replay, ...
```

---

## Getting Started

Turn a blank terminal into a running API in 1 command:

```bash
php siro new my-api && cd my-api && php siro serve
```

Or with Composer:

```bash
composer create-project sirosoft/api my-app
cd my-app && php siro key:generate && php siro serve
```

---

## make:* — Code Generators (26)

Scaffold code instantly. No boilerplate.

| Command | Description |
|---------|-------------|
| `make:crud <name>` | **Full CRUD** — controller, model, migration, routes, tests (`--simple`, `--seed`) |
| `make:auth` | **Auth system** — JWT register, login, refresh, logout, forgot/reset password |
| `make:model <name>` | Model with fillable, casts, table name |
| `make:controller <name>` | Controller class |
| `make:migration <name>` | Migration file |
| `make:service <name>` | Service class (business logic layer) |
| `make:repository <name>` | Repository class (data access layer) |
| `make:resource <name>` | API resource transformer |
| `make:request <name>` | FormRequest class (validation + authorization) |
| `make:middleware <name>` | Middleware class |
| `make:observer <name>` | Model observer class (lifecycle hooks) |
| `make:rule <name>` | Custom validation rule class |
| `make:event <name>` | Event class |
| `make:listener <name>` | Event listener |
| `make:job <name>` | Queue job |
| `make:mail <name>` | Mail class |
| `make:test <name>` | PHPUnit test |
| `make:factory <name>` | Model factory |
| `make:seeder <name>` | Database seeder |
| `make:openapi` | **OpenAPI 3.0.3 spec** — auto-generated from routes + validation + resources (`--with-swagger`) |
| `make:postman` | **Postman collection** — folder structure, auto-login, response examples (`--flow=crud`) |
| `make:lang <locale> <file>` | Language file for i18n |
| `make:queue-table` | Jobs table migration |
| `make:idempotency-table` | Idempotency table migration |
| `make:apikey-table` | API keys table migration |
| `make:apikey <name>` | Generate API key with scopes |

```bash
php siro make:crud products              # 6 files, instantly working
php siro make:crud orders --seed         # CRUD + database seeder
php siro make:auth                        # Full auth scaffolding
php siro make:openapi --with-swagger      # OpenAPI + Swagger UI
php siro make:postman                     # Postman collection
php siro make:apikey "Mobile App" read,write 365
```

> **Killer feature**: `make:openapi` and `make:postman` read your code dynamically — routes, validation rules, resources, auth middleware — and export full OpenAPI 3.0.3 spec / Postman collection. Zero annotation, zero config.

```bash
php siro make:openapi --with-swagger   # → docs/openapi.json + public/docs.html
php siro make:postman                  # → public/postman_collection.json
```

---

## db:* — Database (6)

Manage schema and data without SQL clients.

| Command | Description |
|---------|-------------|
| `migrate` | Run all pending migrations |
| `migrate:rollback` | Rollback last batch (`--step=N`) |
| `migrate:status` | Show migration status (`--pending`) |
| `migrate:fresh` | Drop all tables and re-migrate (`--seed`) |
| `db:seed` | Run database seeders |
| `db:show <table>` | Inspect table schema (`--schema`) |

```bash
php siro migrate                          # Apply pending migrations
php siro migrate:rollback --step=2        # Rollback 2 batches
php siro migrate:status --pending         # Show only pending
php siro migrate:fresh --seed             # Reset + seed data
php siro db:show users                    # Table structure
```

---

## test:* — Testing (5)

Test endpoints, run suites, regression tests — all from CLI.

| Command | Description |
|---------|-------------|
| `test` | Run PHPUnit tests (`--filter`, `--suite`, `--coverage`) |
| `test:run` | Run test with detailed output (`--watch`, `--stop-on-failure`) |
| `test:regression` | Replay all traces, detect response changes (`--limit=N`) |
| `api:test` (alias: `t`) | Quick API test from CLI (no Postman needed) |

### API test — no Postman

```bash
# Login + auto-save token
php siro t POST /api/auth/login email=admin@test.com password=secret --as=admin

# All subsequent requests auto-attach token
php siro t GET /api/products --as=admin
php siro t POST /api/products name=Laptop price=999 --as=admin

# Load test
php siro t GET /api/products --as=admin --loop=100

# Run automated tests
php siro test --filter=Product
php siro test --coverage
```

---

## log:* — Debug & Observability (12)

**Killer feature** — trace every request, replay any failure.

| Command | Description |
|---------|-------------|
| `log:trace <id>` | View full trace (headers, SQL, timing, N+1) |
| `trace:list` | List all traces with filters (`--status`, `--method`, `--ip`, `--path`, `--since`, `--slow`) |
| `log:replay <id>` | **Replay exact request** (`--edit`, `--diff`, `--force`, `--test`) |
| `replay <id>` | Quick replay shortcut |
| `log:export <id>` | Export trace to JSON / Postman format (`--status=500`, `--format=json`) |
| `log:tail` | Tail logs in real-time (`--type`, `--lines`) |
| `log:slow` | Show slow requests (`--limit`, `--min`) |
| `log:stats` | Request statistics (`--days=N`) |
| `log:top` | Top slowest endpoints |
| `log:cleanup` | Clean old logs (`--days=N`, `--dry-run`) |
| `api:why <method> <path>` | **Why did a request fail?** — trace any API call (`--id`, `--edit`, `--fix`, `--diff`, `--force`) |
| `db:why <table> <id>` | Why is this DB row in this state? — trace all queries affecting a row |

### Trace search — find without trace ID

```bash
php siro log:trace --path=/api/orders --status=500 --since=1h
php siro log:trace --ip=203.0.113.42 --error="Division by zero"
php siro log:trace --method=POST --slow --limit=10
```

### Replay — the real moat

```bash
php siro log:replay a1b2c3d4                # Replay (auto-blocks risky traces)
php siro log:replay a1b2c3d4 --dry-run     # Preview without executing
php siro log:replay a1b2c3d4 --edit         # Edit body before replay
php siro log:replay a1b2c3d4 --diff         # Before/after comparison
php siro log:replay a1b2c3d4 --force        # Execute risky trace (DB writes, HTTP, queue)
php siro log:replay a1b2c3d4 --set user_id=42  # Override field
php siro log:replay a1b2c3d4 --format=curl  # Export as curl
php siro log:replay a1b2c3d4 --https        # Use HTTPS

# ⚠ Replay re-executes the request. Potential side effects detected from
# captured SQL writes, outbound HTTP, and queued jobs are guarded by default.
# --force explicitly allows execution.
```

### Replay Safety

Before replaying, Siro analyzes the captured trace for potential side effects:

- **DB writes**: INSERT, UPDATE, DELETE detected in SQL queries
- **Outbound HTTP**: External API calls through Siro's HTTP client
- **Queue jobs**: Async jobs dispatched during the request

Risky traces are blocked by default. Use `--force` to execute.

```bash
php siro log:replay abc123            # safe → auto-executes
php siro log:replay abc123 --dry-run  # preview only
php siro log:replay abc123 --force    # explicit risky execution
```

> ⚠️ Siro detects and warns about side effects but does not sandbox them. A forced replay may still create DB writes, call external APIs, or dispatch jobs.

---

## cache:* — Optimize (4)

Prepare for production — cache everything.

| Command | Description |
|---------|-------------|
| `optimize` | **Full optimization** — config + routes + autoloader |
| `config:cache` | Cache config (HMAC-signed) |
| `config:clear` | Clear config cache |
| `env:cache` | Cache environment (sensitive keys excluded) |

---

## queue:* — Background Jobs (4)

Process jobs, retry failures, monitor status.

| Command | Description |
|---------|-------------|
| `queue:work` | Process jobs (`--daemon`, `--queue`, `--workers=N`) |
| `queue:status` | Show queue status and failed jobs |
| `queue:retry <id\|all>` | Retry failed job(s) |
| `queue:flush` | Clear all failed jobs |

---

## serve:* — Server (4)

Start dev or production server.

| Command | Description |
|---------|-------------|
| `serve` | Dev server (`--port=8080`, `--host`) |
| `live` | Dev server with auto-reload (`--port=9090`) |
| `start` | Interactive onboarding wizard |
| `frankenphp:serve` | Production FrankenPHP (`--docker`, `--port=80`) |

---

## system:* — System (17)

| Command | Description |
|---------|-------------|
| `key:generate` | Generate JWT secret (32+ bytes) |
| `doctor` | System health check (`--prod`) |
| `route:list` | List all routes with middleware |
| `route:search <keyword>` | Search routes by path or handler name |
| `route:rules` | Extract validation rules from all routes |
| `deploy` | Deploy application (`--init`) |
| `down` | Enable maintenance mode (`--message`, `--retry`, `--allow=ip`) |
| `up` | Disable maintenance mode |
| `storage:link` | Create public storage symlink |
| `tinker` | Interactive PHP REPL (like Laravel tinker) |
| `fix` | Watch code changes and auto-replay |
| `rate:status` | Rate limiter status dashboard |
| `env:check` | Validate environment configuration |
| `env:switch <env>` | Switch between environments |
| `benchmark` | Run performance benchmarks (`--iterations=N`, `--json`) |
| `schedule:run` | Run scheduled tasks |
| `new:project <name>` | Scaffold a new Siro project from template |

```bash
php siro doctor --prod                     # Pre-deployment check
php siro route:list                         # All routes
php siro route:search user                  # Find user-related routes
php siro key:generate                       # Fresh JWT secret
php siro tinker                             # PHP REPL
```

---

## Alias System

Commands that you type every day get shorthands:

| Alias | Full Command |
|-------|-------------|
| `php siro why` | `php siro api:why` |
| `php siro slow` | `php siro log:slow` |
| `php siro t` | `php siro api:test` |
| `php siro traces` | `php siro trace:list` |
| `php siro replay` | `php siro log:replay` |

---

## CLI Workflow — From Zero to Production

```bash
# ── 1. Create ──────────────────────────────────────
composer create-project sirosoft/api my-app
cd my-app
php siro key:generate
php siro env:check

# ── 2. Develop ─────────────────────────────────────
php siro make:auth
php siro make:crud Product
php siro make:crud Order
php siro migrate

# ── 3. Test ─────────────────────────────────────────
php siro t POST /api/auth/login ... --as=user
php siro t GET /api/products --as=user
php siro t POST /api/orders ... --as=user --loop=50

# ── 4. Export docs ──────────────────────────────────
php siro make:openapi --with-swagger
php siro make:postman

# ── 5. Deploy ───────────────────────────────────────
php siro doctor --prod
php siro optimize
docker compose up -d
```

**Commands used: 14**
**Third-party tools needed: 0**
**Time to production-ready API: ~5 minutes**

---

# Complete Command Reference

## Make / Generate

| Command | Description | Usage |
|---|---|---|
| `make:auth` | Generate auth system | `php siro make:auth` |
| `make:controller` | Generate controller | `php siro make:controller <name>` |
| `make:model` | Generate model | `php siro make:model <name>` |
| `make:migration` | Generate migration | `php siro make:migration <name>` |
| `make:queue-table` | Generate queue tables migration | `php siro make:queue-table` |
| `make:resource` | Generate API resource transformer | `php siro make:resource <name>` |
| `make:seeder` | Generate seeder | `php siro make:seeder <name>` |
| `make:crud` | Full CRUD scaffolding (--simple, --seed, --force) | `php siro make:crud <name> [--simple] [--seed] [--force]` |
| `make:test` | Generate test file | `php siro make:test <name>` |
| `make:job` | Generate job class | `php siro make:job <name>` |
| `make:mail` | Generate mail class | `php siro make:mail <name>` |
| `make:event` | Generate event class | `php siro make:event <name>` |
| `make:lang` | Generate language file | `php siro make:lang <locale> <file>` |
| `make:factory` | Generate factory | `php siro make:factory <name>` |
| `make:openapi` | Generate OpenAPI spec | `php siro make:openapi [--with-swagger] [--tag=TAG] [--flow=auth\|crud] [--output=] [--force] [--title=]` |
| `make:postman` | Generate Postman collection | `php siro make:postman [--flow=crud]` |
| `make:service` | Generate service class | `php siro make:service <name>` |
| `make:repository` | Generate repository class | `php siro make:repository <name>` |
| `make:middleware` | Generate middleware class | `php siro make:middleware <name>` |
| `make:listener` | Generate event listener | `php siro make:listener <name>` |
| `make:request` | Generate FormRequest class | `php siro make:request <name>` |
| `make:rule` | Generate custom validation rule | `php siro make:rule <name>` |
| `make:observer` | Generate model observer | `php siro make:observer <name>` |
| `make:idempotency-table` | Create idempotency table | `php siro make:idempotency-table` |
| `make:apikey-table` | Create API keys table | `php siro make:apikey-table` |
| `make:apikey` | Generate API key | `php siro make:apikey <name> [scopes] [expires_days]` |

## New Project

| Command | Description | Usage |
|---|---|---|
| `new` | Create new project from skeleton | `php siro new <name>` |
| `new:project` | Create project via composer | `php siro new:project <name>` |

## Database

| Command | Description | Usage |
|---|---|---|
| `migrate` | Run migrations | `php siro migrate` |
| `migrate:fresh` | Drop all tables and re-run all migrations | `php siro migrate:fresh [--seed]` |
| `migrate:rollback` | Rollback migrations | `php siro migrate:rollback [--step=N]` |
| `migrate:status` | Migration status | `php siro migrate:status` |
| `migrate:reset` | Rollback all migrations | `php siro migrate:reset` |
| `migrate:refresh` | Rollback all and re-run migrations | `php siro migrate:refresh [--seed]` |
| `db:seed` | Run seeders | `php siro db:seed` |
| `db:show` | Show table data/schema | `php siro db:show <table> [--schema] [--limit=N]` |
| `db:health` | SQLite database health check | `php siro db:health (SQLite only)` |
| `db:check` | SQLite database integrity check | `php siro db:check (SQLite only)` |
| `db:stats` | SQLite database statistics | `php siro db:stats (SQLite only)` |
| `db:optimize` | Optimize SQLite database | `php siro db:optimize (SQLite only)` |
| `db:backup` | Backup SQLite database | `php siro db:backup [--compress] (SQLite only)` |
| `db:restore` | Restore SQLite database from backup | `php siro db:restore <file> [--force] (SQLite only)` |
| `db:explain` | EXPLAIN query (merged into db:why) | `php siro db:why --query="..."` |
| `db:benchmark` | SQLite performance benchmark | `php siro db:benchmark [--iterations=N] (SQLite only)` |
| `db:why` | Analyze slow query — EXPLAIN, index suggestion | `php siro db:why <query_hash>` |

## Logs

| Command | Description | Usage |
|---|---|---|
| `log:replay` | Replay request (risk-aware: --force for risky traces) | `php siro log:replay <trace_id> [--force] [--dry-run] [--set key=val] [--format=] [--safe]` |
| `log:trace` | View trace details (--full for more) | `php siro log:trace [<id>] [--status=500] [--limit=N] [--full]` |
| `log:export` | Export trace (JSON/CSV/Postman) | `php siro log:export <trace_id> [--format=] [--output=] [--days=] [--curl]` |
| `log:cleanup` | Clean old trace files | `php siro log:cleanup [--days=N] [--dry-run]` |
| `log:slow` | Show slow requests | `php siro log:slow [--limit=N] [--min=MS]` |
| `log:tail` | Tail log files in real-time | `php siro log:tail [--type=request\|error\|slow] [--lines=N] [--follow\|-f]` |
| `log:stats` | Request statistics with charts | `php siro log:stats [--days=N]` |
| `log:top` | Top slowest APIs by total time | `php siro log:top [--limit=N] [--min=MS]` |
| `debug:last` | Show why last request failed (alias: why) | `php siro debug:last` |
| `debug:health` | Check debug system health and configuration | `php siro debug:health` |

## Test

| Command | Description | Usage |
|---|---|---|
| `test:regression` | Replay all traces & compare responses — detect regressions | `php siro test:regression [--limit=N] [--status=500] [--fail]` |
| `test:run` | Run PHPUnit test suite (legacy) | `php siro test:run` |
| `api:test` | Test API (--loop, --as=admin/guest) | `php siro api:test <method> <path> [field:value...] [--as=admin\|guest] [--loop=N]` |
| `test` | Run tests (--filter=, --suite=, --coverage) | `php siro test [--filter=name] [--suite=Unit] [--coverage]` |

## Queue & Schedule

| Command | Description | Usage |
|---|---|---|
| `queue:work` | Process queue jobs | `php siro queue:work [--daemon] [--workers=N]` |
| `queue:retry` | Retry failed jobs | `php siro queue:retry <id\|all>` |
| `queue:flush` | Clear failed jobs | `php siro queue:flush` |
| `queue:status` | Queue statistics | `php siro queue:status` |
| `schedule:run` | Run scheduled tasks | `php siro schedule:run` |

## Server & Deploy

| Command | Description | Usage |
|---|---|---|
| `serve` | Start dev server (php -S) | `php siro serve [--port=8080]` |
| `frankenphp:serve` | Start FrankenPHP production server (--docker) | `php siro frankenphp:serve [--docker] [--port=80]` |
| `live` | Live reload dev server | `php siro live [--port=9090]` |
| `deploy` | Deploy application | `php siro deploy [--init] [--list]` |
| `storage:link` | Create storage symlink | `php siro storage:link` |

## System & Config

| Command | Description | Usage |
|---|---|---|
| `benchmark` | Performance benchmark | `php siro benchmark [--iterations=N] [--json]` |
| `api:why` | Trace a specific request — middleware, SQL, timing, exception | `php siro api:why <METHOD> <path>` |
| `key:generate` | Generate JWT secret | `php siro key:generate` |
| `config:cache` | Cache config | `php siro config:cache` |
| `config:clear` | Clear cached config and routes | `php siro config:clear` |
| `env:cache` | Cache env vars | `php siro env:cache` |
| `optimize` | Optimize for production | `php siro optimize` |
| `env:check` | Check environment | `php siro env:check` |
| `env:switch` | Switch environment | `php siro env:switch <env>` |
| `doctor` | System health check (--prod) | `php siro doctor [--prod]` |
| `fix` | Watch code changes & auto-replay last test | `php siro fix` |
| `down` | Enable maintenance mode | `php siro down [--message="..."] [--retry=60] [--allow=ip1,ip2]` |
| `up` | Disable maintenance mode | `php siro up` |
| `route:list` | List all routes | `php siro route:list` |
| `route:search` | Search routes by keyword | `php siro route:search <keyword>` |
| `route:rules` | Show validation rules | `php siro route:rules` |
| `trace:list` | List recent traces (--limit=N) | `php siro trace:list [--limit=20]` |
| `rate:status` | Rate limit dashboard | `php siro rate:status` |
| `replay` | Replay last trace (risk-aware) | `php siro replay [trace_id] [--force] [--edit] [--diff] [--dry-run]` |
| `runtime` | Siro Runtime manager (install, switch, list) | `php siro runtime [install\|switch\|list\|remove\|current\|path]` |
| `db` | Database manager (init, start, stop) | `php siro db [init\|start\|stop\|status\|remove]` |
| `demo` | 30s debug workflow demo — test, fail, why, fix, trace | `php siro demo` |
| `tinker` | Interactive PHP playground in app context | `php siro tinker` |
| `mercure:subscribe` | Subscribe to Mercure topics and print events | `php siro mercure:subscribe <topic>` |

