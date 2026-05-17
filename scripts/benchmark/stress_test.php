#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * SiroPHP Stress & Performance Test Suite
 *
 * Measures 14 performance metrics covering cold boot, routing,
 * request parsing, response building, database operations, memory,
 * and throughput estimation.
 *
 * Run: php benchmark/stress_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Router;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Database;
use Siro\Core\DB\QueryBuilder;

// ──────────────────────────────────────────────
// Configuration
// ──────────────────────────────────────────────
const COLD_BOOT_WARMUP = 3;
const ROUTE_ITERATIONS = 1000;
const DB_INSERT_COUNT = 1000;
const DB_SELECT_COUNT = 100;
const DB_LIKE_COUNT = 50;
const DB_PAGINATE_COUNT = 50;
const DB_TX_COUNT = 20;
const MIXED_REQUESTS = 100;

const FLAG_THRESHOLD_MS = 5.0;   // flag if avg > 5ms for framework ops
const FLAG_THRESHOLD_RPS = 1000; // flag if < 1000 req/s

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────
function fmtMs(float $ms): string
{
    return number_format($ms, 2) . 'ms';
}

function fmtUs(float $ms): string
{
    return number_format($ms * 1000, 1) . 'μs';
}

function fmtRps(float $rps): string
{
    return number_format($rps, 0) . ' req/s';
}

function fmtMb(float $mb): string
{
    return number_format($mb, 1) . 'MB';
}

function flag(mixed $value, string $threshold, string $unit = 'ms'): string
{
    return " FLAGGED (> {$threshold}{$unit})";
}

function hres(): float
{
    return hrtime(true);
}

function hresMs(float $start): float
{
    return (hrtime(true) - $start) / 1e6;
}

function section(string $title): void
{
    echo "\n--- " . $title . " ---\n";
}

function measure(callable $fn, int $iterations = 1): array
{
    // warmup
    $fn();

    $times = [];
    $startAll = hres();
    for ($i = 0; $i < $iterations; $i++) {
        $t0 = hres();
        $fn();
        $times[] = hresMs($t0);
    }
    $totalMs = hresMs($startAll);

    $avg = $totalMs / $iterations;
    $min = min($times);
    $max = max($times);
    $rps = $totalMs > 0 ? ($iterations / $totalMs * 1000) : 0;

    return [
        'avg' => $avg,
        'min' => $min,
        'max' => $max,
        'total' => $totalMs,
        'iterations' => $iterations,
        'rps' => $rps,
    ];
}

function row(string $label, string $value, string $flagMsg = ''): void
{
    echo str_pad($label, 32) . str_pad($value, 24) . $flagMsg . "\n";
}

// ──────────────────────────────────────────────
// Bootstrap Application
// ──────────────────────────────────────────────

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║              SiroPHP Performance Stress Test                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "PHP " . PHP_VERSION . ' | OS: ' . PHP_OS . ' | ' . date('Y-m-d H:i:s') . "\n\n";

echo "Bootstrapping application... ";

$bootStart = hres();
$app = new App(BASE_PATH);

Router::setMiddlewareAliases([
    'auth' => \App\Middleware\AuthMiddleware::class,
    'throttle' => \App\Middleware\ThrottleMiddleware::class,
    'cors' => \App\Middleware\CorsMiddleware::class,
    'json' => \App\Middleware\JsonMiddleware::class,
]);

$app->boot();
$app->loadRoutes(BASE_PATH . '/routes/api.php');
$bootTimeMs = hresMs($bootStart);
echo "done (" . fmtMs($bootTimeMs) . ")\n";

// ──────────────────────────────────────────────
// Results storage
// ──────────────────────────────────────────────
$results = [];
$flags = [];

// ──────────────────────────────────────────────
// 1. Cold Boot Time
// ──────────────────────────────────────────────
section('1. Cold Boot Time');
row('Test', 'Time (ms)');
row(str_repeat('─', 72), '');

{
    // Re-measure boot more precisely
    $bootTimes = [];
    for ($i = 0; $i < COLD_BOOT_WARMUP + 5; $i++) {
        $t0 = hres();
        $a = new App(BASE_PATH);
        Router::setMiddlewareAliases([
            'auth' => \App\Middleware\AuthMiddleware::class,
            'throttle' => \App\Middleware\ThrottleMiddleware::class,
            'cors' => \App\Middleware\CorsMiddleware::class,
            'json' => \App\Middleware\JsonMiddleware::class,
        ]);
        $a->boot();
        $a->loadRoutes(BASE_PATH . '/routes/api.php');
        $t = hresMs($t0);
        if ($i >= COLD_BOOT_WARMUP) {
            $bootTimes[] = $t;
        }
        unset($a);
    }
    $coldAvg = array_sum($bootTimes) / count($bootTimes);
    $coldMin = min($bootTimes);
    $coldMax = max($bootTimes);
    $results['cold_boot'] = $coldAvg;
    row('Cold boot (boot+loadRoutes)', fmtMs($coldAvg));
    row('  min', fmtMs($coldMin));
    row('  max', fmtMs($coldMax));
    if ($coldAvg > FLAG_THRESHOLD_MS) {
        $flags[] = 'Cold boot: ' . fmtMs($coldAvg) . flag($coldAvg, '5', 'ms');
    }
}

// ──────────────────────────────────────────────
// 2. Route Dispatch — same route
// ──────────────────────────────────────────────
section('2. Route Dispatch Throughput (1000x same route)');

{
    $router = new Router();
    $router->get('/bench/echo', function (Request $req): array {
        return ['success' => true, 'data' => ['pong' => true]];
    });

    $dispatch = function () use ($router): void {
        $req = new Request('GET', '/bench/echo');
        $router->dispatch($req);
    };

    $m = measure($dispatch, ROUTE_ITERATIONS);
    $results['route_dispatch_rps'] = $m['rps'];
    row('Same route (x1000)', fmtMs($m['avg']));
    row('Throughput', fmtRps($m['rps']));
    if ($m['rps'] < FLAG_THRESHOLD_RPS) {
        $flags[] = 'Route dispatch: ' . fmtRps($m['rps']) . flag($m['rps'], '1000', 'req/s');
    }
}

// ──────────────────────────────────────────────
// 3. Dynamic Route Matching
// ──────────────────────────────────────────────
section('3. Dynamic Route Matching (1000x different routes)');

{
    $router = new Router();
    $router->get('/bench/users/{id}', function (Request $req): array {
        return ['success' => true, 'data' => ['id' => $req->param('id')]];
    });

    $m = measure(function () use ($router): void {
        for ($i = 0; $i < 100; $i++) {
            $req = new Request('GET', "/bench/users/$i");
            $router->dispatch($req);
        }
    }, 10); // 10 * 100 = 1000 total
    $results['dynamic_route_rps'] = $m['rps'];
    row('Dynamic routes (x1000)', fmtMs($m['avg']));
    row('Throughput', fmtRps($m['rps']));
    if ($m['rps'] < FLAG_THRESHOLD_RPS) {
        $flags[] = 'Dynamic route: ' . fmtRps($m['rps']) . flag($m['rps'], '1000', 'req/s');
    }
}

// ──────────────────────────────────────────────
// 4. JSON Response Building
// ──────────────────────────────────────────────
section('4. JSON Response Building (1000x)');

{
    // Small payload
    $m = measure(function (): void {
        Response::success(['pong' => true], 'OK');
    }, 1000);
    $results['json_small'] = $m['avg'];
    row('Response::success() small', fmtMs($m['avg']) . '  (' . fmtRps($m['rps']) . ')');

    // Medium payload
    $mediumData = [];
    for ($i = 0; $i < 20; $i++) {
        $mediumData["key_$i"] = str_repeat('x', 100);
    }
    $m = measure(function () use ($mediumData): void {
        Response::success($mediumData, 'OK');
    }, 1000);
    $results['json_medium'] = $m['avg'];
    row('Response::success() medium', fmtMs($m['avg']) . '  (' . fmtRps($m['rps']) . ')');

    // Large payload
    $largeData = [];
    for ($i = 0; $i < 200; $i++) {
        $largeData["key_$i"] = str_repeat('x', 200);
    }
    $m = measure(function () use ($largeData): void {
        Response::success($largeData, 'OK');
    }, 1000);
    $results['json_large'] = $m['avg'];
    row('Response::success() large', fmtMs($m['avg']) . '  (' . fmtRps($m['rps']) . ')');

    // Error response
    $m = measure(function (): void {
        Response::error('Something went wrong', 400, ['field' => 'required']);
    }, 1000);
    $results['json_error'] = $m['avg'];
    row('Response::error()', fmtMs($m['avg']) . '  (' . fmtRps($m['rps']) . ')');
}

// ──────────────────────────────────────────────
// 5. Request Parsing
// ──────────────────────────────────────────────
section('5. Request Parsing (1000x)');

{
    $m = measure(function (): void {
        $req = new Request('POST', '/api/users/42?page=1&limit=20', ['page' => '1', 'limit' => '20'], ['content-type' => 'application/json', 'authorization' => 'Bearer test'], ['name' => 'John', 'email' => 'john@test.com'], '192.168.1.1');
        $req->method();
        $req->path();
        $req->header('authorization');
        $req->body();
        $req->query('page');
        $req->ip();
        $req->input('name');
        $req->param('id');
    }, 1000);
    $results['request_parse'] = $m['avg'];
    row('Request create + access', fmtMs($m['avg']) . '  (' . fmtRps($m['rps']) . ')');
    row('  Per-request', fmtUs($m['avg']));
    if ($m['avg'] > 0.1) {
        $flags[] = 'Request parsing: ' . fmtMs($m['avg']) . ' per op (expect <0.1ms)';
    }
}

// ──────────────────────────────────────────────
// 6–10: Database Tests
// ──────────────────────────────────────────────
section('6–10. Database Operations (SQLite)');

$dbAvailable = true;
try {
    Database::connection()->query('SELECT 1');
} catch (\Throwable $e) {
    $dbAvailable = false;
    $flags[] = 'Database not available: ' . $e->getMessage();
}

if ($dbAvailable) {
    // Ensure bench_products table
    $pdo = Database::connection();
    $pdo->exec('CREATE TABLE IF NOT EXISTS bench_products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        price REAL DEFAULT 0,
        stock INTEGER DEFAULT 0,
        category TEXT,
        status TEXT DEFAULT "active",
        created_at TEXT,
        updated_at TEXT
    )');
    $pdo->exec('DELETE FROM bench_products');

    // ── 6. DB INSERT 1000 rows in transaction ──
    row(str_repeat('─', 72), '');
    $insertStart = hres();
    Database::transaction(function () use ($pdo): void {
        $stmt = $pdo->prepare('INSERT INTO bench_products (name, description, price, stock, category, status) VALUES (?, ?, ?, ?, ?, ?)');
        for ($i = 0; $i < DB_INSERT_COUNT; $i++) {
            $stmt->execute([
                "Product $i",
                "Description for product $i that is moderately long to simulate real data.",
                round(mt_rand(100, 99900) / 100, 2),
                mt_rand(0, 500),
                ['Electronics', 'Clothing', 'Books', 'Home'][$i % 4],
                $i % 10 === 0 ? 'inactive' : 'active',
            ]);
        }
    });
    $insertMs = hresMs($insertStart);
    $results['db_insert'] = $insertMs;
    $results['db_insert_per_row'] = $insertMs / DB_INSERT_COUNT;
    row('6. INSERT ' . DB_INSERT_COUNT . ' rows (tx)', fmtMs($insertMs));
    $insertPerRow = $insertMs / DB_INSERT_COUNT;
    if ($insertPerRow > 2) {
        $flags[] = 'DB INSERT: ' . fmtMs($insertPerRow) . ' avg/row (expect <2ms with SQLite)';
    }

    // ── Count rows ──
    $count = (int) $pdo->query('SELECT COUNT(*) FROM bench_products')->fetchColumn();
    $results['db_insert_count'] = $count;

    // ── 7. DB SELECT by PK ──
    $selectTimes = [];
    for ($i = 0; $i < DB_SELECT_COUNT; $i++) {
        $id = mt_rand(1, DB_INSERT_COUNT);
        $t0 = hres();
        $pdo->prepare('SELECT * FROM bench_products WHERE id = ?')->execute([$id]);
        $selectTimes[] = hresMs($t0);
    }
    $selectAvg = array_sum($selectTimes) / count($selectTimes);
    $results['db_select_pk'] = $selectAvg;
    row('7. SELECT by PK (x' . DB_SELECT_COUNT . ')', fmtMs($selectAvg) . ' avg');
    if ($selectAvg > 1) {
        $flags[] = 'DB SELECT PK: ' . fmtMs($selectAvg) . ' avg (expect <1ms with SQLite)';
    }

    // ── 8. DB LIKE search ──
    $likeTimes = [];
    for ($i = 0; $i < DB_LIKE_COUNT; $i++) {
        $term = "Product " . mt_rand(0, DB_INSERT_COUNT - 1);
        $t0 = hres();
        $pdo->prepare("SELECT * FROM bench_products WHERE name LIKE ?")->execute(["%$term%"]);
        $likeTimes[] = hresMs($t0);
    }
    $likeAvg = array_sum($likeTimes) / count($likeTimes);
    $results['db_like'] = $likeAvg;
    row('8. LIKE search (x' . DB_LIKE_COUNT . ')', fmtMs($likeAvg) . ' avg');
    if ($likeAvg > 3) {
        $flags[] = 'DB LIKE: ' . fmtMs($likeAvg) . ' avg (expect <3ms with SQLite)';
    }

    // ── 9. DB paginate(20) ──
    $paginateTimes = [];
    $qb = new QueryBuilder('bench_products');
    for ($i = 0; $i < DB_PAGINATE_COUNT; $i++) {
        $page = mt_rand(1, max(1, (int) ceil(DB_INSERT_COUNT / 20)));
        $t0 = hres();
        $qb->paginate(20, $page);
        $paginateTimes[] = hresMs($t0);
    }
    $paginateAvg = array_sum($paginateTimes) / count($paginateTimes);
    $results['db_paginate'] = $paginateAvg;
    row('9. paginate(20) (x' . DB_PAGINATE_COUNT . ')', fmtMs($paginateAvg) . ' avg');
    if ($paginateAvg > 5) {
        $flags[] = 'DB paginate: ' . fmtMs($paginateAvg) . ' avg (expect <5ms with SQLite)';
    }

    // ── 10. DB transaction commit + rollback ──
    $commitTimes = [];
    for ($i = 0; $i < DB_TX_COUNT; $i++) {
        $t0 = hres();
        Database::transaction(function () use ($pdo): void {
            $pdo->exec('INSERT INTO bench_products (name, price) VALUES (\'tx_test\', 1.00)');
        });
        $commitTimes[] = hresMs($t0);
    }
    $commitAvg = array_sum($commitTimes) / count($commitTimes);
    $results['db_tx_commit'] = $commitAvg;

    $rollbackTimes = [];
    for ($i = 0; $i < DB_TX_COUNT; $i++) {
        $t0 = hres();
        try {
            Database::transaction(function () use ($pdo): void {
                $pdo->exec('INSERT INTO bench_products (name, price) VALUES (\'tx_rollback\', 1.00)');
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException) {
        }
        $rollbackTimes[] = hresMs($t0);
    }
    $rollbackAvg = array_sum($rollbackTimes) / count($rollbackTimes);
    $results['db_tx_rollback'] = $rollbackAvg;
    row('10. TX commit (x' . DB_TX_COUNT . ')', fmtMs($commitAvg) . ' avg');
    row('    TX rollback (x' . DB_TX_COUNT . ')', fmtMs($rollbackAvg) . ' avg');

    // Cleanup test data
    $pdo->exec('DELETE FROM bench_products WHERE name LIKE \'tx_test\' OR name LIKE \'tx_rollback\'');
} else {
    row('Database unavailable', 'SKIPPED');
}

// ──────────────────────────────────────────────
// 11. Memory Baseline
// ──────────────────────────────────────────────
section('11. Memory Baseline');

$memBoot = memory_get_usage(true);
$memBootPeak = memory_get_peak_usage(true);
$results['mem_baseline'] = $memBoot / 1048576;
$results['mem_baseline_peak'] = $memBootPeak / 1048576;
row('Memory after boot', fmtMb($memBoot / 1048576));
row('Peak after boot', fmtMb($memBootPeak / 1048576));
if ($memBoot / 1048576 > 20) {
    $flags[] = 'Memory baseline: ' . fmtMb($memBoot / 1048576) . ' (expect <20MB)';
}

// ──────────────────────────────────────────────
// 12. Memory Peak After Load
// ──────────────────────────────────────────────
section('12. Memory Peak After 1000 Operations');

$peakMemOps = memory_get_peak_usage(true);

// Generate heavy load to see memory growth
for ($i = 0; $i < 1000; $i++) {
    $req = new Request('GET', "/api/users/$i", [], ['accept' => 'application/json'], [], '127.0.0.1');
    $req->method();
    $req->path();
    $req->header('accept');
    $resp = Response::success(['id' => $i, 'name' => str_repeat('x', 50)]);
    json_encode($resp->payload());
}
$memPeakAfter = memory_get_peak_usage(true);
$memCurrent = memory_get_usage(true);
$results['mem_peak'] = $memPeakAfter / 1048576;
$results['mem_current'] = $memCurrent / 1048576;
row('Peak memory (1000 ops)', fmtMb($memPeakAfter / 1048576));
row('Current memory', fmtMb($memCurrent / 1048576));
row('Growth from boot', fmtMb(($memPeakAfter - $memBoot) / 1048576));
if ($memPeakAfter / 1048576 > 30) {
    $flags[] = 'Memory peak: ' . fmtMb($memPeakAfter / 1048576) . ' (expect <30MB)';
}

// ──────────────────────────────────────────────
// 13. Concurrent Simulation (Sequential Mixed)
// ──────────────────────────────────────────────
section('13. Concurrent Simulation (100 Mixed Requests)');

{
    $router = new Router();
    $router->get('/bench/echo', function (Request $req): array {
        return ['success' => true, 'data' => ['pong' => true]];
    });
    $router->post('/bench/data', function (Request $req): array {
        return ['success' => true, 'data' => $req->body()];
    });
    $router->get('/bench/users/{id}', function (Request $req): array {
        return ['success' => true, 'data' => ['id' => $req->param('id')]];
    });

    $operations = [];
    $ops = [];
    for ($i = 0; $i < MIXED_REQUESTS; $i++) {
        $ops[] = ['GET', '/bench/echo', []];
        $ops[] = ['GET', '/bench/users/' . ($i % 100), []];
        $ops[] = ['POST', '/bench/data', ['name' => "User $i", 'email' => "user$i@test.com"]];
    }

    $m = measure(function () use ($ops): void {
        $router = new Router();
        $router->get('/bench/echo', function (Request $req): array {
            return ['success' => true, 'data' => ['pong' => true]];
        });
        $router->post('/bench/data', function (Request $req): array {
            return ['success' => true, 'data' => $req->body()];
        });
        $router->get('/bench/users/{id}', function (Request $req): array {
            return ['success' => true, 'data' => ['id' => $req->param('id')]];
        });

        foreach ($ops as $op) {
            [$method, $path, $body] = $op;
            $req = new Request($method, $path, $method === 'GET' ? ['page' => '1'] : [], ['content-type' => 'application/json'], $body, '127.0.0.1');
            $router->dispatch($req);
        }
    }, 10);

    $results['mixed_requests_rps'] = $m['rps'];
    row('Mixed (100 req x10 rounds)', fmtMs($m['avg']) . '  (' . fmtRps($m['rps']) . ')');
    row('  Per request', fmtUs($m['avg']));
    if ($m['rps'] < 500) {
        $flags[] = 'Mixed requests: ' . fmtRps($m['rps']) . ' (expect >500 req/s)';
    }
}

// ──────────────────────────────────────────────
// 14. Throughput Estimate
// ──────────────────────────────────────────────
section('14. Throughput Estimate');

{
    $router = new Router();
    $router->get('/bench/echo', function (Request $req): array {
        return ['success' => true, 'data' => ['pong' => true]];
    });

    $req = new Request('GET', '/bench/echo');

    // Measure single dispatch time precisely
    $singleTimes = [];
    for ($i = 0; $i < 100; $i++) {
        $t0 = hres();
        $router->dispatch($req);
        $singleTimes[] = hresMs($t0);
    }
    $singleAvg = array_sum($singleTimes) / count($singleTimes);
    $estRps = $singleAvg > 0 ? 1000 / $singleAvg : 0;

    // Dedicated DB throughput if available
    $dbRps = 0;
    if ($dbAvailable) {
        $t0 = hres();
        $pdo = Database::connection();
        for ($i = 0; $i < 100; $i++) {
            $pdo->query('SELECT 1')->fetchAll();
        }
        $dbQueryTime = hresMs($t0);
        $dbRps = $dbQueryTime > 0 ? 100 / $dbQueryTime * 1000 : 0;
    }

    $results['throughput_est'] = $estRps;
    $results['db_query_rps'] = $dbRps;

    row('Single dispatch avg', fmtUs($singleAvg));
    row('Frame throughput (est)', fmtRps($estRps));
    if ($dbAvailable) {
        row('DB query throughput', fmtRps($dbRps));
    }
    if ($estRps < 10000) {
        $flags[] = 'Throughput estimate: ' . fmtRps($estRps) . ' (expect > 10,000 req/s)';
    }
}

// ──────────────────────────────────────────────
// Summary Report
// ──────────────────────────────────────────────
echo "\n\n";
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    STRESS TEST REPORT                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo str_pad('Metric', 32) . str_pad('Value', 22) . "Notes\n";
echo str_repeat('─', 80) . "\n";

$summaryRows = [
    ['Cold boot (boot+loadRoutes)', fmtMs($results['cold_boot'] ?? 0), ''],
    ['Route dispatch (same)', fmtRps($results['route_dispatch_rps'] ?? 0),
        $results['route_dispatch_rps'] < 1000 ? 'SLOW' : ''],
    ['Dynamic route matching', fmtRps($results['dynamic_route_rps'] ?? 0),
        $results['dynamic_route_rps'] < 1000 ? 'SLOW' : ''],
    ['JSON response (small)', fmtMs($results['json_small'] ?? 0), ''],
    ['JSON response (medium)', fmtMs($results['json_medium'] ?? 0), ''],
    ['JSON response (large)', fmtMs($results['json_large'] ?? 0), ''],
    ['JSON response (error)', fmtMs($results['json_error'] ?? 0), ''],
    ['Request parsing (each)', fmtUs($results['request_parse'] ?? 0), ''],
    ['DB INSERT ' . DB_INSERT_COUNT . ' rows', fmtMs($results['db_insert'] ?? 0),
        ($results['db_insert_per_row'] ?? 0) > 2 ? 'SLOW ('.fmtMs($results['db_insert_per_row']).'/row)' : ''],
    ['DB SELECT by PK', fmtMs($results['db_select_pk'] ?? 0) . ' avg',
        $results['db_select_pk'] > 1 ? 'SLOW' : ''],
    ['DB LIKE search', fmtMs($results['db_like'] ?? 0) . ' avg',
        $results['db_like'] > 3 ? 'SLOW' : ''],
    ['DB paginate(20)', fmtMs($results['db_paginate'] ?? 0) . ' avg',
        $results['db_paginate'] > 5 ? 'SLOW' : ''],
    ['DB TX commit', fmtMs($results['db_tx_commit'] ?? 0) . ' avg', ''],
    ['DB TX rollback', fmtMs($results['db_tx_rollback'] ?? 0) . ' avg', ''],
    ['Memory baseline', fmtMb($results['mem_baseline'] ?? 0),
        ($results['mem_baseline'] ?? 0) > 20 ? 'HIGH' : ''],
    ['Memory peak (1000 ops)', fmtMb($results['mem_peak'] ?? 0),
        ($results['mem_peak'] ?? 0) > 30 ? 'HIGH' : ''],
    ['Mixed requests', fmtRps($results['mixed_requests_rps'] ?? 0),
        $results['mixed_requests_rps'] < 500 ? 'SLOW' : ''],
    ['Throughput estimate', fmtRps($results['throughput_est'] ?? 0),
        $results['throughput_est'] < 10000 ? 'LOW' : ''],
];

foreach ($summaryRows as $r) {
    echo str_pad($r[0], 32) . str_pad($r[1], 22) . ($r[2] ? " ⚠ $r[2]" : '') . "\n";
}

echo str_repeat('─', 80) . "\n";
echo str_pad('PHP Version', 32) . PHP_VERSION . "\n";
echo str_pad('Platform', 32) . PHP_OS . ' ' . php_uname('r') . "\n";
echo str_pad('Date', 32) . date('Y-m-d H:i:s') . "\n";
echo str_pad('OPcache', 32) . (extension_loaded('Zend OPcache') ? 'enabled' : 'disabled') . "\n";
echo str_pad('JIT', 32) . (defined('PHP_JIT') && PHP_JIT ? (string) ini_get('opcache.jit') : 'disabled') . "\n";

// Flags
if ($flags !== []) {
    echo "\n" . str_repeat('═', 80) . "\n";
    echo " ⚠ PERFORMANCE FLAGS\n";
    echo str_repeat('─', 80) . "\n";
    foreach ($flags as $f) {
        echo "  • $f\n";
    }
}

echo "\n✓ Stress test complete.\n\n";
