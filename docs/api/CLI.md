# CLI Command Reference

## Overview

Siro ships with 72 CLI commands. Run `php siro list` or `php siro <command> --help` for details.

---

## Getting Help

```bash
php siro                    # Core workflow overview
php siro list               # List all 72 commands
php siro <command> --help   # Detailed help
php siro -h                 # Shorthand help
php siro --version          # Show version
```

---

## make:* — Code Generators (23)

| Command | Description |
|---------|-------------|
| `make:auth` | Full auth system (JWT, register, login, forgot/reset) |
| `make:crud <name>` | Full CRUD (controller, model, migration, routes, tests) |
| `make:controller <name>` | Controller class |
| `make:model <name>` | Model class |
| `make:migration <name>` | Migration file |
| `make:service <name>` | Service class |
| `make:repository <name>` | Repository class |
| `make:middleware <name>` | Middleware class |
| `make:resource <name>` | API resource transformer |
| `make:request <name>` | FormRequest class |
| `make:listener <name>` | Event listener |
| `make:event <name>` | Event class |
| `make:job <name>` | Queue job |
| `make:mail <name>` | Mail class |
| `make:test <name>` | PHPUnit test |
| `make:factory <name>` | Model factory |
| `make:seeder <name>` | Database seeder |
| `make:openapi` | OpenAPI 3.0 spec |
| `make:postman` | Postman collection |
| `make:lang <locale> <file>` | Language file |
| `make:queue-table` | Jobs table migration |
| `make:idempotency-table` | Idempotency table migration |
| `make:apikey-table` | API keys table migration |
| `make:apikey <name>` | Generate API key |

### Examples

```bash
php siro make:crud products              # Full CRUD
php siro make:crud orders --simple       # Without relations
php siro make:crud orders --seed         # With seeder
php siro make:auth                        # Auth scaffolding
php siro make:openapi --with-swagger      # Swagger UI
php siro make:apikey "Mobile App" read,write 365
```

---

## db:* — Database (6)

| Command | Description |
|---------|-------------|
| `migrate` | Run pending migrations |
| `migrate:rollback` | Rollback last batch (`--step=N`) |
| `migrate:status` | Migration status (`--pending`) |
| `migrate:fresh` | Drop all + re-migrate (`--seed`) |
| `db:seed` | Run seeders |
| `db:show <table>` | Show table schema |

### Examples

```bash
php siro migrate                          # Run all
php siro migrate:rollback --step=2        # Rollback 2 batches
php siro migrate:status --pending         # Pending only
php siro migrate:fresh --seed             # Reset DB + seed
php siro db:show users                    # Inspect users table
```

---

## cache:* — Cache & Config (5)

| Command | Description |
|---------|-------------|
| `config:cache` | Cache config (HMAC-signed) |
| `config:clear` | Clear config cache |
| `env:cache` | Cache environment |
| `env:check` | Validate environment |
| `optimize` | Full production optimization |

### Examples

```bash
php siro optimize                          # Full optimization
php siro config:cache                      # Cache config
php siro env:check                         # Validate .env
```

---

## log:* — Debug & Logs (10)

| Command | Description |
|---------|-------------|
| `log:tail` | Tail logs (`--type`, `--lines`) |
| `log:slow` | Slow requests (`--limit`, `--min`) |
| `log:stats` | Log statistics (`--days`) |
| `log:top` | Top slowest endpoints |
| `log:cleanup` | Clean old logs |
| `log:trace <id>` | View trace (with search filters) |
| `log:replay <id>` | Replay request |
| `log:export <id>` | Export trace |
| `log:search` | Search traces |
| `why` | Last request analysis |

### Trace Search Filters

```bash
php siro log:trace --path=/api/orders       # By endpoint
php siro log:trace --status=500             # By status
php siro log:trace --ip=203.0.113.42        # By IP
php siro log:trace --error="SQL"            # By error text
php siro log:trace --since=30m              # By time range
php siro log:trace --method=POST            # By HTTP method
php siro log:trace --slow                   # Only slow (>100ms)
php siro log:trace --days=7                 # Last 7 days
php siro log:trace --limit=20               # Max results
```

### Replay Options

```bash
php siro log:replay a1b2c3d4                # Dry-run (safe)
php siro log:replay a1b2c3d4 --edit         # Interactive edit
php siro log:replay a1b2c3d4 --diff         # Before/after diff
php siro log:replay a1b2c3d4 --force        # Execute replay
php siro log:replay a1b2c3d4 --set user_id=42  # Override field
php siro log:replay a1b2c3d4 --format=curl  # As curl command
php siro log:replay a1b2c3d4 --https        # Use HTTPS
```

---

## queue:* — Queue (4)

| Command | Description |
|---------|-------------|
| `queue:work` | Process next job (`--daemon`, `--queue`) |
| `queue:status` | Failed jobs list |
| `queue:retry` | Retry failed (`--all`, `--id=N`) |
| `queue:flush` | Clear all failed jobs |

---

## serve:* — Server (3)

| Command | Description |
|---------|-------------|
| `serve` | Dev server (`--port`, `--host`) |
| `start` | Interactive onboarding |
| `frankenphp:serve` | Production FrankenPHP |

---

## test:* — Testing (3)

| Command | Description |
|---------|-------------|
| `test` | Run all tests (`--coverage`, `--filter`) |
| `t` | Quick API test from CLI |
| `benchmark` | Performance benchmarks |

### API Test Options

```bash
php siro t POST /api/auth/login email=admin@test.com password=secret --as=admin
php siro t GET /api/products --as=admin
php siro t POST /api/products name=Laptop price=999 --as=admin
php siro t GET /api/products --as=admin --loop=100  # Load test
```

---

## system:* — System (16)

| Command | Description |
|---------|-------------|
| `key:generate` | Generate JWT secret |
| `doctor` | System health check (`--prod`) |
| `deploy` | Deploy application (`--init`) |
| `route:list` | List all routes |
| `route:search <term>` | Search routes |
| `up` | Disable maintenance mode |
| `down` | Enable maintenance mode |
| `schedule:run` | Run scheduled tasks |
| `tinker` | Interactive PHP REPL |
| `api:test` | CLI API testing |
| `make:openapi` | OpenAPI generation |
| `make:postman` | Postman generation |
| `env:check` | Environment validation |
| `benchmark` | Performance benchmark |
| `optimize` | Production optimization |
| `storage:link` | Create storage symlink |

---

## Alias System

| Alias | Full Command |
|-------|-------------|
| `php siro why` | `php siro debug:last` |
| `php siro slow` | `php siro log:slow` |
| `php siro t` | `php siro api:test` |
| `php siro traces` | `php siro trace:list` |
