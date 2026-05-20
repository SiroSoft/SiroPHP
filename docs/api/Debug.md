# Debug API Reference

## Overview

Siro's debug system provides request tracing, replay, and production debugging tools — no third-party services required.

```bash
php siro why                     # Last request analysis
php siro log:trace <id>          # View full trace
php siro log:replay <id>         # Replay request
```

---

## Trace System

Every request gets a unique trace ID returned in the response header:

```http
X-Siro-Trace-Id: siro_a1b2c3d4e5f6
```

### Search Traces

```bash
# By endpoint
php siro log:trace --path=/api/orders

# By HTTP status
php siro log:trace --status=500

# By IP address (customer says "my IP is...")
php siro log:trace --ip=203.0.113.42

# By error message
php siro log:trace --error="Division by zero"

# By time
php siro log:trace --since=30m
php siro log:trace --since=1h
php siro log:trace --days=7

# By HTTP method
php siro log:trace --method=POST

# Slow requests only
php siro log:trace --slow

# Limit results
php siro log:trace --limit=20
```

### View Trace Details

```bash
php siro log:trace siro_a1b2c3d4
```

Output includes:
- Method, path, status code
- Request headers and body
- SQL queries with timing
- Middleware execution timeline
- Exception message and stack trace
- N+1 query detection
- Total execution time

---

## Request Replay

The signature Siro feature — replay any captured request.

### Safe Replay (production default)

```bash
# Dry-run (default in production — no side effects)
php siro log:replay siro_a1b2c3d4

# With diff (compare before/after fix)
php siro log:replay siro_a1b2c3d4 --diff
```

### Interactive Replay

```bash
# Edit request body before replay
php siro log:replay siro_a1b2c3d4 --edit

# Override specific fields
php siro log:replay siro_a1b2c3d4 --set user_id=42

# Output as curl command
php siro log:replay siro_a1b2c3d4 --format=curl

# Output as httpie command
php siro log:replay siro_a1b2c3d4 --format=httpie
```

### Force Execution

```bash
# Execute replay with side effects (use with caution)
php siro log:replay siro_a1b2c3d4 --force

# With HTTPS
php siro log:replay siro_a1b2c3d4 --force --https

# Skip SSL verification
php siro log:replay siro_a1b2c3d4 --force --insecure
```

### Export

```bash
# Export trace to JSON
php siro log:export siro_a1b2c3d4

# Export failed requests as JSON
php siro log:export --status=500 --format=json
```

### Complete Debug Flow

```
INCIDENT → SEARCH → INSPECT → REPLAY → DIFF → VERIFY

1. Customer reports error, no trace ID
2. php siro log:trace --path=/api/orders --status=500 --since=1h
3. php siro log:trace siro_a1b2c3d4        # See full context
4. php siro log:replay siro_a1b2c3d4 --edit # Test fix
5. php siro log:replay siro_a1b2c3d4 --diff # Before vs after
6. php siro log:replay siro_a1b2c3d4 --force # Verify fix
```

No other framework — PHP, Node, Go, Rust, Python, Ruby — has this complete flow.

---

## Log Management

```bash
# Tail logs in real-time
php siro log:tail
php siro log:tail --type=error
php siro log:tail --lines=50

# Slow request report
php siro log:slow
php siro log:slow --limit=10
php siro log:slow --min=500   # requests over 500ms

# Log statistics
php siro log:stats
php siro log:stats --days=7

# Top slowest endpoints
php siro log:top

# Clean old logs
php siro log:cleanup
php siro log:cleanup --days=14   # custom retention
```

---

## Health Checks

```bash
# System health
php siro doctor

# Production health check
php siro doctor --prod

# HTTP health endpoints
GET /health/live    # Liveness probe
GET /health/ready   # Readiness probe (includes DB check)
```

---

## Debug CLI

```bash
# Why did the last request fail?
php siro why
# 5 seconds later: route, SQL, middleware, exception, N+1, suggested fix

# Interactive PHP REPL (like Laravel tinker)
php siro tinker

# Environment validation
php siro env:check

# Benchmark
php siro benchmark
```

---

## Trace Data Contents

When a trace is captured, it includes:

| Field | Description |
|-------|-------------|
| `trace_id` | Unique identifier |
| `method` | HTTP method |
| `path` | Request path |
| `status_code` | Response status |
| `headers` | Request headers (sensitive values redacted) |
| `body` | Request body |
| `queries` | Array of SQL queries with timing |
| `middleware` | Middleware execution timeline |
| `exception` | Exception class and message |
| `trace` | Stack trace (debug mode only) |
| `duration_ms` | Total request duration |
| `memory_mb` | Memory usage |
| `n_plus_one` | N+1 query detection results |
| `ip` | Client IP |
| `timestamp` | Request timestamp |

---

## Available Commands

| Command | Description |
|---------|-------------|
| `why` | Last request analysis with N+1 detection |
| `log:trace <id>` | View trace details |
| `log:replay <id>` | Replay request (--edit, --diff, --force) |
| `log:tail` | Tail log files |
| `log:slow` | Show slow requests |
| `log:stats` | Log statistics |
| `log:top` | Top slowest endpoints |
| `log:export <id>` | Export trace to JSON |
| `log:cleanup` | Clean old logs |
| `tinker` | Interactive PHP REPL |
| `doctor` | System health check |
| `env:check` | Environment validation |
| `benchmark` | Performance benchmarks |
