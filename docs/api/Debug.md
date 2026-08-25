---
title: D eb ug
description: SiroPHP D eb ug reference
sidebar_position: 6
sidebar_label: D eb ug
---

# Debug API Reference

## Overview

Siro's debug system provides request tracing, replay, and production debugging tools — no third-party services required.

```bash
php siro why                     # Last request analysis
php siro api:why POST /orders    # Trace specific request by method+path
php siro log:trace <id>          # View full trace
php siro log:replay <id>         # Replay request (risk-aware)
php siro log:replay <id> --force # Replay risky trace (DB writes, HTTP calls)
php siro log:replay <id> --dry-run  # Preview without executing
php siro fix <id>                # Replay + verify fix
php siro log:replay <id> --test  # Generate regression test from trace
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

The signature Siro feature — replay any captured request with **risk-aware safety**.

### How Replay Safety Works

Before replaying, Siro analyzes the trace for potential side effects:

- **DB writes**: INSERT, UPDATE, DELETE, REPLACE, TRUNCATE detected in captured SQL
- **Outbound HTTP**: External API calls made through Siro's HTTP client
- **Queue jobs**: Async jobs dispatched during the request

```
Potential replay side effects:
  Database writes:   3
  Outbound HTTP:     1
  Queue dispatches:  1

WARNING: This command re-executes the request against the target application.
Database writes, external API calls, queued jobs, emails, or other
application side effects may occur again.
```

**Guard behavior:**
- GET + no risks → auto-executes (safe)
- POST/PUT/DELETE/PATCH → requires `--force`
- Any method + risks detected → requires `--force`

> ⚠️ Siro detects and warns about side effects. It does **not** sandbox or isolate them. A forced replay of a checkout request may still create DB writes, call payment APIs, or dispatch jobs.

### Safe Replay

```bash
# Auto-executes if no risks detected
php siro log:replay siro_a1b2c3d4

# Preview without executing (always safe)
php siro log:replay siro_a1b2c3d4 --dry-run

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
# Execute replay (required for risky traces or write methods)
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
| `request_headers` | Request headers (sensitive values redacted) |
| `request_body` | Request body |
| `response_body` | Response body |
| `queries` | Array of SQL queries with timing and row counts |
| `middleware` | Middleware execution timeline |
| `outbound_http` | External HTTP calls via Siro\Http (method, URL, status, duration) |
| `queue_jobs` | Jobs dispatched during request (job name, source trace ID) |
| `exception` | Exception class and message |
| `duration_ms` | Total request duration |
| `ip` | Client IP |
| `timestamp` | Request timestamp |

> **Note:** `outbound_http` only captures requests through `Siro\Core\Http`. Native cURL/Guzzle calls are not captured.

---

## Available Commands

| Command | Description |
|---------|-------------|
| `why` | Last request analysis with N+1 detection |
| `log:trace <id>` | View trace details |
| `log:replay <id>` | Replay request (risk-aware: --force for risky traces, --dry-run to preview) |
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
