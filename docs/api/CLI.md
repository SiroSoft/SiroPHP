# CLI Command Reference

## Overview

Siro ships with **72 CLI commands**. Every task — from project creation to production debugging — is done from the terminal. No GUI tools needed.

```bash
php siro                    # Core workflow overview
php siro list               # All 72 commands grouped
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

## make:* — Code Generators (23)

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
| `make:request <name>` | FormRequest class (validation + authorization) |
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

## export:* — API Documentation (2)

**Killer feature** — export full OpenAPI spec and Postman collection from code. Zero annotation, zero config.

| Command | Description |
|---------|-------------|
| `make:openapi` | **OpenAPI 3.0.3** with operationId, request/response schemas, auth |
| `make:postman` | **Postman collection** with folders, auto-login, response examples |

### Cơ chế dynamic — thêm API là spec tự cập nhật

| Export reads from | What gets generated |
|------------------|-------------------|
| `$app->router->getRoutes()` | All endpoints, methods, paths |
| `Controller::validate([...])` | Request body schemas + examples |
| `Resource::toArray()` | Response body schemas + examples |
| Middleware `auth` detection | Bearer auth on protected routes |
| Controller class names | Tags (Products, Orders, Users...) |

```bash
# Code → spec trong 2 giây
php siro make:openapi --with-swagger
# → docs/openapi.json      (145KB, 27 endpoints, 45 operationIds)
# → public/openapi.json     (public URL)
# → public/docs.html        (Swagger UI at http://localhost:8080/docs.html)

# Code → Postman trong 2 giây
php siro make:postman
# → docs/postman/collection.json
# → public/postman_collection.json

# Import vào Postman bằng URL:
# http://localhost:8080/postman_collection.json
```

---

## test:* — Testing & Benchmarks (4)

Test endpoints, run suites, benchmark performance — all from CLI.

| Command | Description |
|---------|-------------|
| `test` | Run PHPUnit tests (`--filter`, `--suite`, `--coverage`) |
| `api:test` (alias: `t`) | Quick API test from CLI (no Postman needed) |
| `benchmark` | Run performance benchmarks (`--iterations=N`, `--json`) |

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

## log:* — Debug & Observability (10)

**Killer feature** — trace every request, replay any failure.

| Command | Description |
|---------|-------------|
| `log:trace <id>` | View full trace (headers, SQL, timing, N+1) |
| `log:replay <id>` | **Replay exact request** (`--edit`, `--diff`, `--force`) |
| `log:export <id>` | Export trace to JSON / Postman format |
| `log:tail` | Tail logs in real-time (`--type`, `--lines`) |
| `log:slow` | Show slow requests (`--limit`, `--min`) |
| `log:stats` | Request statistics (`--days=N`) |
| `log:top` | Top slowest endpoints |
| `log:cleanup` | Clean old logs (`--days=N`, `--dry-run`) |
| `debug:last` (alias: `why`) | Why did the last request fail? |
| `debug:health` | Debug system health |

### Trace search — find without trace ID

```bash
php siro log:trace --path=/api/orders --status=500 --since=1h
php siro log:trace --ip=203.0.113.42 --error="Division by zero"
php siro log:trace --method=POST --slow --limit=10
```

### Replay — the real moat

```bash
php siro log:replay a1b2c3d4                # Dry-run (safe)
php siro log:replay a1b2c3d4 --edit         # Edit body before replay
php siro log:replay a1b2c3d4 --diff         # Before/after comparison
php siro log:replay a1b2c3d4 --force        # Execute (verify fix)
php siro log:replay a1b2c3d4 --set user_id=42  # Override field
php siro log:replay a1b2c3d4 --format=curl  # Export as curl
php siro log:replay a1b2c3d4 --https        # Use HTTPS
```

---

## cache:* — Optimize (5)

Prepare for production — cache everything, validate environment.

| Command | Description |
|---------|-------------|
| `optimize` | **Full optimization** — env + config + routes + autoloader |
| `config:cache` | Cache config (HMAC-signed) |
| `config:clear` | Clear config cache |
| `env:cache` | Cache environment (sensitive keys excluded) |
| `env:check` | Validate environment configuration |

```bash
php siro optimize                          # Production optimization
php siro config:cache                      # Cache only config
php siro env:check                         # Validate .env
```

---

## queue:* — Background Jobs (4)

Process jobs, retry failures, monitor status.

| Command | Description |
|---------|-------------|
| `queue:work` | Process jobs (`--daemon`, `--queue`, `--workers=N`) |
| `queue:status` | Show queue status and failed jobs |
| `queue:retry <id\|all>` | Retry failed job(s) |
| `queue:flush` | Clear all failed jobs |
| `schedule:run` | Run scheduled tasks |

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

## system:* — System (14)

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
| `replay <trace_id>` | Quick replay shortcut |
| `rate:status` | Rate limiter status dashboard |
| `env:switch <env>` | Switch between environments |

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
| `php siro why` | `php siro debug:last` |
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

**Commands used: 0**
**Third-party tools needed: 0**
**Time to production-ready API: ~5 minutes**
