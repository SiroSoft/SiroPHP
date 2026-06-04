<?php

declare(strict_types=1);

/**
 * SiroPHP Debug Workflow Demo — 30-Second Pipeline
 *
 * Shows the complete debug cycle: test → fail → why → fix → retry → trace → replay.
 *
 * Run: php siro demo
 * Or:  php scripts/demo.php
 */

// ── Simulate debug workflow output ─────────────────────

echo "\n";
echo "  ╔══════════════════════════════════════════════════╗\n";
echo "  ║     SiroPHP Debug Workflow Demo                 ║\n";
echo "  ║     30s to see the full debug pipeline           ║\n";
echo "  ╚══════════════════════════════════════════════════╝\n\n";

// Step 1 — Failing API test
echo "  ── Step 1: Test API endpoint ──────────────────────\n";
echo "\n";
echo "  \$ php siro api:test POST /api/products --body='{\"name\":\"Test\"}'\n";
echo "\n";
echo "    POST /api/products\n";
echo "    Status: 422\n";
echo "    Time:   12.3ms\n";
echo "    Memory: 4.2MB\n";
echo "\n";
echo "    Body:\n";
echo '    {"success":false,"message":"Validation failed",' . "\n";
echo '     "errors":{"price":["The price field is required."]}}' . "\n";
echo "\n";
usleep(500000);

// Step 2 — Analyze failure
echo "  ── Step 2: Analyze failure ────────────────────────\n";
echo "\n";
echo "  \$ php siro api:why POST /api/products\n";
echo "\n";
echo "    ─────────────────────────────────────────────────\n";
echo "    Analysis for: POST /api/products\n";
echo "    ─────────────────────────────────────────────────\n";
echo "\n";
echo "    Test failed because: Validation failed\n";
echo "    Missing required field: price\n";
echo "    Suggestion: Add 'price' to request body\n";
echo "\n";
echo "    Validation Rules:\n";
echo "      name:     required|string|max:255          ✓\n";
echo "      price:    required|numeric|min:0           ← MISSING\n";
echo "      category: required|exists:categories,id\n";
echo "\n";
echo "    Auth token: ✓ Valid (admin scope, expires in 2h)\n";
echo "    Headers:    ✓ Content-Type, ✓ Accept, ✓ Authorization\n";
echo "\n";
usleep(500000);

// Step 3 — Fix and retry
echo "  ── Step 3: Fix and retry ─────────────────────────\n";
echo "\n";
echo "  \$ php siro api:test POST /api/products \\\n";
echo "      --body='{\"name\":\"Test\",\"price\":10,\"category\":1}'\n";
echo "\n";
echo "    POST /api/products\n";
echo "    Status: 201\n";
echo "    Time:   8.7ms\n";
echo "    Memory: 4.1MB\n";
echo "\n";
echo "    Body:\n";
echo '    {"success":true,"data":{"id":1,"name":"Test","price":10,"category_id":1}}' . "\n";
echo "\n";
usleep(500000);

// Step 4 — Show traces
echo "  ── Step 4: Browse traces ─────────────────────────\n";
echo "\n";
echo "  \$ php siro trace:list --limit=5\n";
echo "\n";
echo "    Latest traces:\n";
echo "    ┌──────────┬──────┬─────────────────┬──────┬──────────────┐\n";
echo "    │ Trace ID │ Meth │ Path            │ Stat │ Time         │\n";
echo "    ├──────────┼──────┼─────────────────┼──────┼──────────────┤\n";
echo "    │ f8a2c1d  │ POST │ /api/products   │ 422  │ 30 sec ago   │\n";
echo "    │ b3e4f5a  │ POST │ /api/products   │ 201  │ 25 sec ago   │ ← latest\n";
echo "    │ a1c2d3e  │ GET  │ /api/products   │ 200  │ 5 min ago    │\n";
echo "    │ c4d5e6f  │ POST │ /api/orders     │ 500  │ 15 min ago   │\n";
echo "    │ e7f8a9b  │ GET  │ /api/health     │ 200  │ 1 hour ago   │\n";
echo "    └──────────┴──────┴─────────────────┴──────┴──────────────┘\n";
echo "\n";
usleep(500000);

// Step 5 — Inspect a trace
echo "  ── Step 5: Inspect a trace ────────────────────────\n";
echo "\n";
echo "  \$ php siro log:trace b3e4f5a\n";
echo "\n";
echo "    ── Trace: b3e4f5a ─────────────────────────────────\n";
echo "    Route:    POST /api/products\n";
echo "    Status:   201\n";
echo "    Duration: 8.7ms\n";
echo "    IP:       127.0.0.1\n";
echo "\n";
echo "    ── Middleware Timeline ─────────────────────────\n";
echo "    ✓ VersionMiddleware     0.01ms\n";
echo "    ✓ CorsMiddleware        0.01ms\n";
echo "    ✓ ThrottleMiddleware    0.12ms\n";
echo "    ✓ AuthMiddleware        0.28ms\n";
echo "    ✓ JsonMiddleware        0.01ms\n";
echo "\n";
echo "    ── SQL Queries ────────────────────────────────\n";
echo "    1. SELECT * FROM categories WHERE id = ?   [0.3ms, 1 row]\n";
echo "    2. INSERT INTO products (...) VALUES (...)  [1.2ms, 1 row]\n";
echo "\n";
echo "    ── N+1 Detection ─────────────────────────────\n";
echo "    No N+1 detected.\n";
echo "\n";
usleep(500000);

// Step 6 — Replay a trace
echo "  ── Step 6: Replay a request ───────────────────────\n";
echo "\n";
echo "  \$ php siro replay b3e4f5a --dry-run\n";
echo "\n";
echo "    Dry run — no request sent\n";
echo "    Method: POST\n";
echo "    URL:    http://localhost:8080/api/products\n";
echo "    Body:   {\"name\":\"Test\",\"price\":10,\"category\":1}\n";
echo "    Auth:   Bearer [token present — scope: admin]\n";
echo "    Headers:\n";
echo "      Content-Type: application/json\n";
echo "      Accept:       application/json\n";
echo "\n";
echo "    To execute: php siro replay b3e4f5a --force\n";
echo "\n";
usleep(500000);

// Summary
echo "  ╔══════════════════════════════════════════════════╗\n";
echo "  ║  Complete debug cycle: 5 seconds                 ║\n";
echo "  ║  Traditional approach: 2-5 minutes               ║\n";
echo "  ╚══════════════════════════════════════════════════╝\n";
echo "\n";
echo "  Commands used in this demo:\n";
echo "    php siro api:test           Test any endpoint from CLI\n";
echo "    php siro api:why            Explain why a test failed\n";
echo "    php siro trace:list         Browse captured traces\n";
echo "    php siro log:trace          Inspect full request context\n";
echo "    php siro replay             Replay any past request\n";
echo "\n";
echo "  To learn more:\n";
echo "    php siro <command> --help\n";
echo "    cat docs/guides/DEBUG_WORKFLOW.md\n";
echo "\n";
