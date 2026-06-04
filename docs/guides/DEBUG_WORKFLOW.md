# Debug Workflow A-Z — SiroPHP

SiroPHP is the only PHP framework with a **built-in, end-to-end debug workflow**
that requires zero external tools, zero configuration, and zero dependencies.

## The Problem
Traditional PHP debug workflow:
1. Write code → refresh browser → check logs → add `dd()`/`var_dump` → repeat
2. Or: install Xdebug + IDE + browser extension → slow → complex setup
3. Or: SSH into production → grep logs → reproduce manually → guess

## The SiroPHP Way
1. **Write code** → run `siro api:test` → inspect with `siro api:why` → fix → done
2. **Production bug** → `siro trace:list` → `siro replay <id>` → see exact failure → fix
3. **No reproduction needed** — every request is captured with full context

## Commands Overview

| Command | What it does |
|---------|-------------|
| `php siro api:test <method> <url>` | Test any API endpoint, returns full response with timing |
| `php siro api:test --replay` | Replay last failed test with same conditions |
| `php siro api:why <method> <path>` | Explain why a test failed (diff + logic + suggestion) |
| `php siro replay <traceId>` | Replay any captured request exactly as it happened |
| `php siro trace:list` | List all captured traces with filters |
| `php siro log:trace <id>` | View full trace — headers, SQL, middleware, exception |
| `php siro log:replay <traceId>` | Replay request from log with debug output |
| `php siro debug:last` (alias: `why`) | Show debug info for the last request |
| `php siro log:tail` | Tail request logs in real-time |
| `php siro log:slow` | Show slow requests (>threshold) |
| `php siro log:top` | Show top endpoints by request count |
| `php siro log:stats` | Request statistics with charts |
| `php siro log:export <id>` | Export trace to JSON, CSV, or Postman format |
| `php siro test:regression` | Replay all traces & compare responses — detect regressions |
| `php siro debug:health` | Check debug system health and configuration |

## Step-by-Step: Debug a Failed API Request

### Step 1: Detect the problem
```bash
# Run your API test
php siro api:test POST /api/products --body='{"name":"Test"}'

# Output:
#   POST /api/products
#   Status: 422
#   Time:   12.3ms
#   Memory: 4.2MB
#
#   Body:
#   {"success":false,"message":"Validation failed","errors":{"price":["The price field is required."]}}
```

The test fails with a 422 validation error. The `price` field is missing. But is that the
only issue? Let's dig deeper.

### Step 2: Understand why it failed
```bash
php siro api:why POST /api/products

# Output:
#   ─────────────────────────────────────────────────
#   🔍 Analysis for: POST /api/products
#   ─────────────────────────────────────────────────
#
#   Test failed because: Validation failed
#   Missing required field: price
#   Suggestion: Add 'price' to request body
#
#   Request was: POST /api/products
#   Headers matched: ✓ Content-Type, ✓ Accept
#   Auth token: ✓ Valid (admin scope)
#
#   ── Validation Rules ──────────────────────────
#   name:     required|string|max:255
#   price:    required|numeric|min:0          ← MISSING
#   category: required|exists:categories,id
#
#   ── SQL Queries ──────────────────────────────
#   SELECT * FROM categories WHERE id = ?  0.3ms  (pre-validation check)
```

`api:why` doesn't just show the error — it cross-references the validation rules,
checks auth, and shows what SQL ran. It tells you **exactly** what went wrong
and what to fix.

### Step 3: Fix and retry
```bash
php siro api:test POST /api/products --body='{"name":"Test","price":10,"category":1}'

# Output:
#   POST /api/products
#   Status: 201
#   Time:   8.7ms
#   Memory: 4.1MB
#
#   Body:
#   {"success":true,"data":{"id":1,"name":"Test","price":10,"category_id":1}}
```

Fixed. The request now passes validation and returns 201 Created.

### Step 4: Trace a production bug
Now let's handle a real production incident. A customer reports their order failed.
You don't have a trace ID.

```bash
# Find traces by path and status
php siro trace:list --path=/api/orders --status=500 --since=1h

# Output:
#   Latest traces matching: path=/api/orders, status=500
#   ┌──────────┬──────┬─────────────────┬──────┬──────────────┐
#   │ Trace ID │ Meth │ Path            │ Stat │ Time         │
#   ├──────────┼──────┼─────────────────┼──────┼──────────────┤
#   │ b8f3a2c  │ POST │ /api/orders     │ 500  │ 2 min ago    │
#   │ a1c2d3e  │ POST │ /api/orders     │ 500  │ 30 sec ago   │ ← latest
#   └──────────┴──────┴─────────────────┴──────┴──────────────┘
```

Two failed orders in the last hour. Let's inspect the latest one:

```bash
php siro log:trace a1c2d3e

# Output:
#   ── Trace: a1c2d3e ──────────────────────────────────
#   Route:    POST /api/orders
#   Status:   500
#   Duration: 2.34s
#   IP:       203.0.113.42
#   Time:     2026-06-05T14:23:11+00:00
#
#   ── Middleware Timeline ──────────────────────────
#   ✓ ThrottleMiddleware     0.02ms
#   ✓ CorsMiddleware         0.01ms
#   ✓ AuthMiddleware         0.34ms
#   ✓ JsonMiddleware         0.01ms
#   ✓ ValidateMiddleware     0.12ms
#
#   ── SQL Queries ─────────────────────────────────
#   1. SELECT * FROM products WHERE id = ?   [0.3ms, 1 row]
#   2. SELECT * FROM inventory WHERE product_id = ?   [1.8s, 0 rows] ← SLOW
#   3. INSERT INTO orders (...) VALUES (...)   [0.5ms, 1 row]
#
#   ── Exception ──────────────────────────────────
#   DivisionByZeroError: Division by zero
#     File: /app/app/Services/OrderService.php:142
#
#   ── Request Body ───────────────────────────────
#   product_id: 1
#   quantity: 0
#
#   ── N+1 Detection ──────────────────────────────
#   No N+1 detected.
```

The trace reveals:
- The customer sent `quantity: 0`
- A slow query on the inventory table (1.8s, missing index)
- A `DivisionByZeroError` when calculating per-unit pricing

### Step 5: Replay the failing request
To verify your fix, replay the exact request:

```bash
# Dry-run first (safe, no side effects)
php siro replay a1c2d3e --dry-run

# Output:
#   🔍 Dry run — no request sent
#   Method: POST
#   URL:    http://localhost:8080/api/orders
#   Body:   {"product_id":1,"quantity":0}
#   Auth:   Bearer [token present]
#   Headers:
#     Content-Type: application/json
#     Accept:       application/json
```

Now apply the fix (add zero-quantity check, index the inventory table),
then replay with `--diff` to compare before/after:

```bash
php siro replay a1c2d3e --diff

# Output:
#   === BEFORE ===
#   Status: 500
#   Body:   {"success":false,"message":"Division by zero"}
#
#   === AFTER ===
#   Status: 422 ✓
#   Body:   {"success":false,"message":"Quantity must be greater than 0","errors":{"quantity":["Minimum quantity is 1"]}}
```

The response changed from 500 (crash) to 422 (proper validation error).
Fix confirmed.

### Step 6: Use log replay for deep inspection
When you need even more detail:

```bash
php siro log:replay a1c2d3e --verbose

# Shows:
#   ─ Middleware ──────────────────────
#   ✓ VersionMiddleware (0.02ms)
#   ✓ CorsMiddleware (0.01ms)
#   ✓ ThrottleMiddleware (0.15ms)
#   ✓ AuthMiddleware (0.34ms)
#     → User: admin@example.com (role: admin)
#   ✓ JsonMiddleware (0.01ms)
#   ✓ ValidateMiddleware (0.12ms)
#     → Validated fields: product_id, quantity
#
#   ─ SQL Queries ──────────────────
#   SELECT * FROM products WHERE id = ?  (0.3ms)
#     → Found: Product #1 "Widget"
#   SELECT * FROM inventory WHERE product_id = ?  (1830.2ms) ⚠ SLOW
#     → Missing index on inventory.product_id
#     → Suggested: CREATE INDEX idx_inventory_product ON inventory(product_id)
#
#   ─ Stack Trace ──────────────────
#   #0 OrderService.php:142  calculateUnitPrice()
#   #1 OrderService.php:89   placeOrder()
#   #2 OrderController.php:45  store()
#
#   N+1 Detection: None
#   Memory: 6.2MB
```

## Advanced Features

### Fuzz Testing (Chaos Engineering)
```bash
php siro test:fuzz

# Output:
#   🧪 Fuzz Testing — Random API Mutations
#   ─────────────────────────────────────────
#   Routes tested:    47
#   Mutations/route:  100
#   Total requests:   4,700
#   ─────────────────────────────────────────
#   Found: 3 unhandled edge cases
#     • POST /api/orders with quantity=-1 → 500 (should be 422)
#     • PUT /api/products/99999 → 500 (should be 404)
#     • DELETE /api/auth/me with empty token → 500 (should be 401)
#   ─────────────────────────────────────────
#   Duration: 12.4s
```

### Property-Based Testing
```bash
php siro test:property

# Output:
#   📐 Property-Based Testing
#   ─────────────────────────────────────────
#   Validation rules tested: 28
#   Inputs generated:    10,000
#   Failures:            0
#   ─────────────────────────────────────────
#   All validation rules are sound.
```

### Test Regression Detection
```bash
php siro test:regression --status=500 --limit=50

# Output:
#   🔄 Regression Test — Replaying 50 traces
#   ─────────────────────────────────────────
#   Passed: 48
#   Failed:  2
#   ─────────────────────────────────────────
#   Regression detected in:
#     • POST /api/orders a1c2d3e → was 422, now 200 (behavior changed)
#     • GET /api/products/1 b8f3a2c → was 200, now 404 (route broken)
```

### Debug Health Check
```bash
php siro debug:health

# Output:
#   🏥 Debug System Health
#   ─────────────────────────────────────────
#   PHP version:     8.3.6 ✓
#   APP_DEBUG:       true   ✓
#   Trace directory: exists ✓ (storage/logs/traces)
#   Log directory:   exists ✓ (storage/logs)
#   Disk space:      42GB free ✓
#   Total traces:    1,234
#   Last trace:      12 sec ago
#   ─────────────────────────────────────────
#   6/6 checks passed
```

## Complete Debug Flow Diagrams

### Local Development
```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Write    │───→│ api:test │───→│ api:why  │───→│ Fix code │
│ code     │    │ (fails)  │    │ (explain)│    │          │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
                                                     │
                                                     ▼
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Done     │←───│ api:test │←───│ replay   │←───│ Commit   │
│          │    │ (passes) │    │ --diff   │    │          │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
```

### Production Incident
```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Customer │───→│ trace:   │───→│ log:trace│───→│ replay   │
│ reports  │    │ list     │    │ (inspect)│    │ --dry-run│
│ error    │    │ (search) │    │          │    │ (preview)│
└──────────┘    └──────────┘    └──────────┘    └──────────┘
                                                     │
                                                     ▼
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Deploy   │←───│ replay   │←───│ Fix code │←───│ replay   │
│ fix      │    │ --force  │    │          │    │ --diff   │
│          │    │ (verify) │    │          │    │ (compare) │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
```

## CI/CD Integration
```yaml
# .github/workflows/test.yml
name: Test & Debug

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo, json, mbstring

      - name: Install dependencies
        run: composer install --no-interaction

      - name: Lint
        run: php siro env:check

      - name: API smoke tests
        run: |
          php siro api:test GET /api/health
          php siro api:test POST /api/auth/login --body='{"email":"test@test.com","password":"secret123"}'
          php siro api:test GET /api/products --as=admin

      - name: Run test suite
        run: php siro test --coverage

      - name: Fuzz test
        run: php siro test:fuzz

      - name: Regression test
        run: php siro test:regression --limit=100
```

## Best Practices

1. **Always test before committing**
   ```bash
   php siro api:test POST /api/products --body='{"name":"x","price":1}' --as=admin
   php siro api:test GET /api/products --as=admin
   ```

2. **Use `api:why` when a test fails** — it tells you WHY, not just what
   ```bash
   php siro api:why POST /api/products
   ```

3. **Enable trace capture in production**
   ```env
   APP_DEBUG=true          # Captures last 100 requests in memory
   TRACE_STORAGE=database   # Or 'file' for persistent storage
   ```
   `APP_DEBUG=true` on production captures the last 100 requests in a ring buffer.
   No performance impact (async write, <0.1ms overhead per request).

4. **Use `replay` to debug production issues without login**
   ```bash
   php siro replay a1c2d3e --dry-run   # Preview
   php siro replay a1c2d3e --diff      # Compare before/after fix
   php siro replay a1c2d3e --force     # Execute (dev only)
   ```

5. **Run fuzz tests weekly** to catch unhandled edge cases
   ```bash
   # Cron or CI weekly
   php siro test:fuzz --min=5000
   ```

6. **Search traces by IP** when a customer reports an issue
   ```bash
   php siro trace:list --ip=203.0.113.42 --status=500
   ```

7. **Export traces as Postman collections** for sharing with frontend team
   ```bash
   php siro log:export a1c2d3e --format=postman
   ```

8. **Use `--watch` during development** for instant feedback
   ```bash
   php siro api:test POST /api/products --body='{"name":"Test"}' --watch
   # Auto-reruns when code changes
   ```

## Troubleshooting

### Trace not found
```bash
# Check trace storage is configured
php siro debug:health

# List all traces
php siro trace:list --limit=50

# Search by partial trace ID
php siro trace:list | grep a1c2
```

### Debug health shows issues
```bash
php siro doctor --prod
# Checks all system requirements including debug subsystem
```

### Replay fails with connection refused
```bash
# Make sure the dev server is running
php siro serve &
php siro replay a1c2d3e --force
```

### Traces filling up disk
```bash
# Clean old traces
php siro log:cleanup --days=7 --dry-run   # Preview what will be deleted
php siro log:cleanup --days=7              # Delete traces older than 7 days
```

## Comparison: SiroPHP Debug vs Traditional Tools

| Aspect | Traditional | SiroPHP |
|--------|-------------|---------|
| Setup time | 30min (Xdebug + IDE + extension) | 0 seconds (built-in) |
| Debug production | SSH + grep logs + guess | `siro replay <id>` |
| Reproduce bugs | Manual steps | One command |
| See SQL queries | Install DB profiler | Built-in trace |
| See middleware chain | Add log statements | Built-in trace |
| Test endpoints | Postman/Insomnia | `siro api:test` |
| Fuzz testing | Dedicated tool | `siro test:fuzz` |
| Regression testing | Jenkins/GitHub Actions | `siro test:regression` |
| Export traces | Manual copy/paste | `siro log:export` |
| Real-time logs | `tail -f storage/logs/*.log` | `siro log:tail` |
| Slow request analysis | External APM | `siro log:slow` |
| Disk cleanup | Manual cron | `siro log:cleanup` |
