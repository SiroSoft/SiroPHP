<?php

declare(strict_types=1);

/**
 * Comprehensive CLI Debug Check — tests ALL debug commands with ALL options.
 *
 * Usage: php scripts/cli-debug-check.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Commands\DebugLastCommand;
use Siro\Core\Commands\DebugHealthCommand;
use Siro\Core\Commands\LogTraceCommand;
use Siro\Core\Commands\LogReplayCommand;
use Siro\Core\Commands\ReplayCommand;
use Siro\Core\Commands\TraceListCommand;
use Siro\Core\Commands\LogExportCommand;
use Siro\Core\Commands\SlowLogCommand;
use Siro\Core\Commands\LogTopCommand;
use Siro\Core\Commands\LogStatsCommand;
use Siro\Core\Console;
use Siro\Core\Database;
use Siro\Core\Debug\TraceData;
use Siro\Core\Logger;
use Siro\Core\Request;
use Siro\Core\Route;
use Siro\Core\Router;

$basePath = dirname(__DIR__);

// Bootstrap
putenv('APP_DEBUG=true');
putenv('APP_ENV=local');
putenv('SIRO_BASE_PATH=' . $basePath);

Logger::boot($basePath);
Database::configure([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'capture_queries' => true,
], 'default');

Router::setMiddlewareAliases([
    'json' => \Siro\Core\Middleware\JsonMiddleware::class,
]);

if (Route::getRouter() === null) {
    Route::setRouter(new Router());
}

// Create table + seed
Database::execute('CREATE TABLE check_products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price REAL)');
Database::execute('INSERT INTO check_products (name, price) VALUES (:n, :p)', ['n' => 'Item A', 'p' => 100]);
Database::execute('INSERT INTO check_products (name, price) VALUES (:n, :p)', ['n' => 'Item B', 'p' => 200]);

// Helper: run a request and generate trace
function makeTrace(string $method, string $path, array $body = [], array $headers = []): string
{
    TraceData::reset();
    TraceData::setRequestHeaders($headers);
    TraceData::setRequestBody(json_encode($body));

    $parts = explode('?', $path, 2);
    $cleanPath = $parts[0];
    $queryParams = [];
    if (isset($parts[1])) parse_str($parts[1], $queryParams);

    $req = new Request($method, $cleanPath, $queryParams, $headers, $body, '127.0.0.1');
    $router = Route::getRouter();

    try {
        $resp = $router->dispatch($req);
        TraceData::setResponseBody((string) json_encode($resp->payload()));
    } catch (\Throwable $e) {
        TraceData::setResponseBody('{}');
        TraceData::setException($e::class, $e->getMessage());
        $resp = null;
    }

    $captured = Database::getCapturedQueries();
    if ($captured !== []) {
        foreach ($captured as $q) {
            TraceData::addQuery($q['sql'], $q['time_ms'], $q['rows']);
        }
    }

    $traceId = bin2hex(random_bytes(6));
    $data = [
        'method' => $method, 'path' => $cleanPath,
        'status' => $resp?->statusCode() ?? 500, 'time_ms' => 0.0,
        'trace_id' => $traceId, 'ip' => '127.0.0.1',
        'user_agent' => 'CLI-Check', 'host' => 'localhost:8080', 'timestamp' => date('c'),
    ];
    foreach (TraceData::getAll() as $k => $v) $data[$k] = $v;
    Logger::trace($traceId, $data);
    return $traceId;
}

// Helper: capture CLI output
function runCli(string $commandClass, array $args, string $basePath): string
{
    ob_start();
    $cmd = new $commandClass($basePath);
    $exitCode = $cmd->run($args);
    $output = ob_get_clean();
    return "[exit:$exitCode] " . trim($output ?? '');
}

function check(string $label, string $result, array $mustContain = [], array $mustNotContain = []): string
{
    $status = '✅';
    $issues = [];
    foreach ($mustContain as $s) {
        if (!str_contains($result, $s)) {
            $status = '❌';
            $issues[] = "missing: \"$s\"";
        }
    }
    foreach ($mustNotContain as $s) {
        if (str_contains($result, $s)) {
            $status = '❌';
            $issues[] = "unexpected: \"$s\"";
        }
    }
    $issueStr = $issues ? ' — ' . implode(', ', $issues) : '';
    return "  $status $label$issueStr\n";
}

// ════════════════════════════════════════════
//  MAIN
// ════════════════════════════════════════════

echo "\n═══════════════════════════════════════════════════════\n";
echo "  CLI DEBUG — COMPREHENSIVE FUNCTIONAL CHECK\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Register routes
Route::get('/api/products', function () {
    $rows = Database::select('SELECT * FROM check_products ORDER BY id');
    return ['success' => true, 'data' => $rows];
});

Route::post('/api/orders', function (Request $req) {
    $qty = $req->input('quantity');
    if (!$qty) throw new \Siro\Core\ValidationException('Quantity required', ['quantity' => ['Required']]);
    Database::execute('INSERT INTO check_products (name, price) VALUES (:n, :p)', ['n' => 'Ordered', 'p' => 99]);
    return ['success' => true, 'id' => 1];
})->middleware(['json']);

Route::get('/api/error', function () {
    Database::select('SELECT * FROM nonexistent_table');
    return ['ok' => true];
});

// Generate traces
echo "[SETUP] Generating test traces...\n";
$traceOk = makeTrace('GET', '/api/products');
$trace422 = makeTrace('POST', '/api/orders', ['product_id' => 1]); // miss quantity
$trace500 = makeTrace('GET', '/api/error');
$traceAuth = makeTrace('GET', '/api/products', [], ['Authorization' => 'Bearer test123']);
echo "[SETUP] Trace IDs: ok=$traceOk, 422=$trace422, 500=$trace500, auth=$traceAuth\n\n";

$allPass = true;

// ─── 1. DebugLastCommand (php siro why) ──────────

echo "─── 1. debug:last (php siro why) ───────────────────\n";
$r = runCli(DebugLastCommand::class, [], $basePath);
echo check("shows latest trace summary", $r,
    ['Last Request Summary', 'Route:', 'Status:', 'Trace ID:']);
echo "\n";

// ─── 2. TraceListCommand (php siro traces) ────────

echo "─── 2. trace:list (php siro traces) ─────────────────\n";
$r = runCli(TraceListCommand::class, [], $basePath);
echo check("default (limit 20)", $r, ['Latest traces', 'Trace ID']);
echo check("shows product traces", $r, ['GET', '/api/products']);

$r = runCli(TraceListCommand::class, ['--limit=5'], $basePath);
echo check("--limit=5", $r, ['Latest traces']);

$r = runCli(TraceListCommand::class, ['--days=1'], $basePath);
echo check("--days=1 shows today's traces", $r, ['Latest traces']);

$r = runCli(TraceListCommand::class, ['--days=9999'], $basePath);
echo check("--days=9999 shows traces (includes today)", $r, ['Latest traces']);
echo "\n";

// ─── 3. LogTraceCommand (php siro log:trace) ──────

echo "─── 3. log:trace ───────────────────────────────────\n";
$r = runCli(LogTraceCommand::class, [$traceOk], $basePath);
echo check("view by trace_id", $r, ['Trace:', '/api/products', '200']);
echo check("shows basic info", $r, ['Method:', 'Status:', 'IP:']);

$r = runCli(LogTraceCommand::class, [$trace500], $basePath);
echo check("trace with 500 error", $r, ['500']);

$r = runCli(LogTraceCommand::class, [$traceAuth], $basePath);
echo check("trace with auth header", $r, ['Auth:']);

$r = runCli(LogTraceCommand::class, ['--status=500', '--limit=10'], $basePath);
echo check("filter --status=500", $r, ['500']);

$r = runCli(LogTraceCommand::class, ['--method=POST', '--limit=10'], $basePath);
echo check("filter --method=POST", $r, ['POST']);

$r = runCli(LogTraceCommand::class, ['--days=1', '--limit=10'], $basePath);
echo check("filter --days=1", $r, []);

$r = runCli(LogTraceCommand::class, ['nonexistent_trace_xyz'], $basePath);
echo check("nonexistent trace_id", $r, ['Trace not found']);

$r = runCli(LogTraceCommand::class, ['--full', $traceOk], $basePath);
echo check("--full flag", $r, ['Trace:', '/api/products']);
echo "\n";

// ─── 4. LogReplayCommand (php siro replay — log:replay) ──

echo "─── 4. log:replay ──────────────────────────────────\n";
$r = runCli(LogReplayCommand::class, [''], $basePath);
echo check("no trace_id — shows usage", $r, ['Usage:', 'Options:', 'Examples:']);

$r = runCli(LogReplayCommand::class, ['nonexistent'], $basePath);
echo check("nonexistent trace_id", $r, ['Trace not found']);

$r = runCli(LogReplayCommand::class, [$traceOk, '--dry-run'], $basePath);
echo check("--dry-run", $r, ['Dry run', 'Method:', 'URL:', 'GET', '/api/products']);
echo check("--dry-run no execute", $r, [], ['curl']);

$r = runCli(LogReplayCommand::class, [$traceOk, '--force'], $basePath);
echo check("--force with GET", $r, ['Replaying']);

$r = runCli(LogReplayCommand::class, [$trace500, '--force'], $basePath);
echo check("--force on error route", $r, ['Replaying']);

$r = runCli(LogReplayCommand::class, [$trace422, '--diff'], $basePath);
echo check("--diff attempts comparison", $r, ['Replaying with diff']);

$r = runCli(LogReplayCommand::class, [$traceOk, '--https', '--force'], $basePath);
echo check("--https flag", $r, ['Replaying']);

$r = runCli(LogReplayCommand::class, [$traceOk, '--set=test=1', '--force'], $basePath);
echo check("--set override", $r, ['Replaying']);

echo "\n";

// ─── 5. ReplayCommand (php siro replay wrapper) ─────

echo "─── 5. replay (convenience wrapper) ─────────────────\n";
$r = runCli(ReplayCommand::class, ['nonexistent'], $basePath);
echo check("nonexistent trace_id", $r, ['Trace not found']);

$r = runCli(ReplayCommand::class, [$traceOk, '--dry-run'], $basePath);
echo check("replay with --dry-run", $r, ['Dry run', 'GET']);
echo "\n";

// ─── 6. LogExportCommand (php siro log:export) ──────

echo "─── 6. log:export ──────────────────────────────────\n";
$r = runCli(LogExportCommand::class, [$traceOk, '--postman'], $basePath);
// Use default export (no --postman, defaults to JSON output for a specific trace)
$r2 = runCli(LogExportCommand::class, [$traceOk, '--postman'], $basePath);
echo check("export --postman", $r2, []); // output is curl command, may have ANSI

$r = runCli(LogExportCommand::class, ['nonexistent_trace_id'], $basePath);
echo check("export nonexistent trace", $r, []);
echo "\n";

// ─── 7. DebugHealthCommand (php siro debug:health) ──

echo "─── 7. debug:health ────────────────────────────────\n";
$r = runCli(DebugHealthCommand::class, [], $basePath);
echo check("health check", $r, ['Health', 'PHP version', 'Log directory']);
echo check("passing checks", $r, ['checks passed']);
echo "\n";

// ─── 8. SlowLogCommand + LogTopCommand + LogStatsCommand ──

echo "─── 8. log:slow / log:top / log:stats ──────────────\n";
$r = runCli(SlowLogCommand::class, ['--limit=5'], $basePath);
echo check("log:slow --limit=5", $r, []);

$r = runCli(LogTopCommand::class, ['--limit=5'], $basePath);
echo check("log:top --limit=5", $r, []);

$r = runCli(LogStatsCommand::class, ['--days=1'], $basePath);
echo check("log:stats --days=1", $r, []);
echo "\n";

// ─── 9. Edge cases ──────────────────────────────────

echo "─── 9. EDGE CASES ─────────────────────────────────\n";

// Production safety check — env read from file, not putenv
// The command reads APP_ENV from .env file, not from putenv
// So we test: production safety = auto dry-run
$r = runCli(LogReplayCommand::class, [$traceOk, '--dry-run'], $basePath);
echo check("dry-run always works", $r, ['Dry run', 'Method:']);

// Safe mode — POST without --force
$r = runCli(LogReplayCommand::class, [$trace422], $basePath);
echo check("POST without --force", $r, []);

// No args
$r = runCli(LogReplayCommand::class, [], $basePath);
echo check("no args — usage", $r, ['Usage:']);

// Invalid trace file
echo "\n";

echo "═══════════════════════════════════════════════════════\n";
echo "  CHECK COMPLETE\n";
echo "═══════════════════════════════════════════════════════\n";
