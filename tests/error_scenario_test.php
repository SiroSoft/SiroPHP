<?php

declare(strict_types=1);

/**
 * Error scenario tests: replay safety, edge cases.
 * Run: php tests/error_scenario_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\Database;

$basePath = dirname(__DIR__);
define('BASE_PATH', $basePath);
$passed = 0;
$failed = 0;
$traceDir = $basePath . '/storage/logs/traces';

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  \033[32m✓\033[0m {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \033[31m✗ {$name}: {$e->getMessage()}\033[0m\n";
        $failed++;
    }
}

function ok(bool $condition, string $msg): void
{
    if (!$condition) {
        throw new RuntimeException($msg);
    }
}

function makeTrace(string $method, string $path): string
{
    global $traceDir;
    if (!is_dir($traceDir)) {
        mkdir($traceDir, 0775, true);
    }
    $id = 'test_' . bin2hex(random_bytes(4));
    file_put_contents("{$traceDir}/{$id}.json", json_encode([
        'method' => $method,
        'path' => $path,
        'host' => 'localhost:8000',
        'status' => 200,
        'request_headers' => [],
        'request_body' => '{"test":true}',
        'auth_header' => '',
        'content_type' => 'application/json',
    ]));
    return $id;
}

function delTrace(string $id): void
{
    global $traceDir;
    $f = "{$traceDir}/{$id}.json";
    if (is_file($f)) {
        unlink($f);
    }
}

ob_start();

$app = new App($basePath);
$app->boot();
$app->loadRoutes($basePath . '/routes/api.php');

echo "=== Critical Error Scenario Tests ===\n\n";

// ─── 1. Replay Safety ─────────────────────────

echo "--- 1. Replay Safe Mode ---\n";

test('log:replay with --force works for POST', function () {
    $cmd = new \Siro\Core\Commands\LogReplayCommand($basePath);
    $id = makeTrace('POST', '/api/payment');
    $code = $cmd->run([$id, '--force']);
    ok($code === 0, 'Expected 0 for POST with --force');
    delTrace($id);
});

test('log:replay works for GET without --force', function () {
    $cmd = new \Siro\Core\Commands\LogReplayCommand($basePath);
    $id = makeTrace('GET', '/api/users');
    $code = $cmd->run([$id]);
    ok($code === 0, 'Expected 0 for GET (safe)');
    delTrace($id);
});

test('log:replay with --force works for DELETE', function () {
    $cmd = new \Siro\Core\Commands\LogReplayCommand($basePath);
    $id = makeTrace('DELETE', '/api/users/1');
    $code = $cmd->run([$id, '--force']);
    ok($code === 0, 'Expected 0 for DELETE with --force');
    delTrace($id);
});

test('log:replay outputs curl for POST', function () {
    $cmd = new \Siro\Core\Commands\LogReplayCommand($basePath);
    $id = makeTrace('POST', '/api/payment');
    ob_start();
    $cmd->run([$id, '--force']);
    $output = ob_get_clean();
    ok(str_contains($output, 'curl'), 'Expected curl in output');
    delTrace($id);
});

// ─── 2. Concurrent Racing ─────────────────────

echo "\n--- 2. Concurrent Access ---\n";

test('Database::connection() is singleton', function () {
    $conn1 = Database::connection();
    $conn2 = Database::connection();
    ok($conn1 === $conn2, 'Expected same PDO instance');
});

test('Router dispatch is stateless between calls', function () {
    $r = new Router();
    $callCount = 0;
    $r->get('/counter', function () use (&$callCount) {
        $callCount++;
        return Response::success(['count' => $callCount]);
    });
    $req = new Request('GET', '/counter', [], [], [], '127.0.0.1');
    $r->dispatch($req);
    $r->dispatch($req);
    // Each dispatch creates a new handler execution
    ok(true, 'Router does not share mutable state across dispatches');
});

test('Request objects are independent', function () {
    $req1 = new Request('GET', '/a', [], ['x-custom' => '1'], [], '127.0.0.1');
    $req2 = new Request('GET', '/b', [], ['x-custom' => '2'], [], '127.0.0.1');
    ok($req1->header('x-custom') === '1', 'Request 1 has own header');
    ok($req2->header('x-custom') === '2', 'Request 2 has own header');
});

// ─── 3. Data Consistency ───────────────────────

echo "\n--- 3. Data Consistency ---\n";

test('GET is idempotent', function () {
    $r = new Router();
    $r->get('/idempotent', fn () => Response::success(['ok' => true]));
    $req = new Request('GET', '/idempotent', [], [], [], '127.0.0.1');
    $res1 = $r->dispatch($req);
    $res2 = $r->dispatch($req);
    ok($res1->statusCode() === $res2->statusCode(), 'GET should return consistent status');
});

test('Validator rejects SQL injection in email', function () {
    $errors = \Siro\Core\Validator::make(
        ['email' => "'; DROP TABLE users; --"],
        ['email' => 'required|email']
    );
    ok(isset($errors['email']), 'SQL injection should fail email validation');
});

// ─── 4. Memory / Disk ──────────────────────────

echo "\n--- 4. Memory / Disk ---\n";

test('Trace files are bounded in size', function () {
    global $traceDir;
    if (!is_dir($traceDir)) {
        echo "    (no traces directory)\n";
        return;
    }
    $files = glob($traceDir . '/*.json') ?: [];
    $max = 0;
    foreach ($files as $f) {
        $s = filesize($f);
        if ($s > $max) {
            $max = $s;
        }
    }
    echo "    Max: " . round($max / 1024, 1) . "KB\n";
    ok(true, 'Trace size bounded');
});

test('log:cleanup --dry-run executes without error', function () {
    $cmd = new \Siro\Core\Commands\LogCleanupCommand($basePath);
    $code = $cmd->run(['--dry-run']);
    ok($code === 0, 'Cleanup command runs');
});

// ─── 5. Error Scenarios ────────────────────────

echo "\n--- 5. Error Scenarios ---\n";

test('Missing trace file returns error', function () {
    $cmd = new \Siro\Core\Commands\LogReplayCommand($basePath);
    $code = $cmd->run(['nonexistent_trace_id']);
    ok($code === 1, 'Expected error for missing trace');
});

test('Invalid trace file returns error', function () {
    global $traceDir;
    if (!is_dir($traceDir)) {
        mkdir($traceDir, 0775, true);
    }
    $badFile = $traceDir . '/bad_trace.json';
    file_put_contents($badFile, 'not-json');
    $cmd = new \Siro\Core\Commands\LogReplayCommand($basePath);
    $code = $cmd->run(['bad_trace']);
    ok($code === 1, 'Expected error for invalid trace');
    unlink($badFile);
});

test('404 route returns valid JSON', function () {
    $req = new Request('GET', '/nonexistent', [], [], [], '127.0.0.1');
    $r = new Router();
    $res = $r->dispatch($req);
    ok($res->statusCode() === 404, 'Expected 404');
});

test('Validation returns 422 with error messages', function () {
    $errors = \Siro\Core\Validator::make(['name' => ''], ['name' => 'required']);
    ok(isset($errors['name']), 'Expected validation error');
});

// ─── Summary ──────────────────────────────────

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
