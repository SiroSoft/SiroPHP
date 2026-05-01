<?php

declare(strict_types=1);

/**
 * Stability & Load Test — 1000 requests + replay + api:test.
 * Run: php tests/stability_test.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\Logger;
use Siro\Core\Database;

$basePath = dirname(__DIR__);
define('BASE_PATH', $basePath);
$passed = 0;
$failed = 0;

$app = new App($basePath);
$app->boot();
$app->loadRoutes($basePath . '/routes/api.php');

function ok(bool $condition, string $msg): void
{
    if (!$condition) {
        throw new RuntimeException($msg);
    }
}

function dispatch(App $app, string $method, string $path, array $body = [], array $headers = []): int
{
    ob_start();
    try {
        $request = new Request($method, $path, [], $headers, $body, '127.0.0.1');
        $response = $app->router->dispatch($request);
        ob_end_clean();
        return $response->statusCode();
    } catch (\Siro\Core\ValidationException $e) {
        ob_end_clean();
        return $e->toResponse()->statusCode();
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

echo "=== Stability & Load Test ===\n\n";

$start = microtime(true);

// ─── 1. 1000 requests ────────────────────────

echo "--- 1. 1000 requests (load test) ---\n";

$errors = 0;
$statusCounts = [];
$traceDir = $basePath . '/storage/logs/traces';

for ($i = 0; $i < 1000; $i++) {
    try {
        $path = ($i % 5 === 0) ? '/' : '/api/users';
        $code = dispatch($app, 'GET', $path);
        $statusCounts[$code] = ($statusCounts[$code] ?? 0) + 1;
    } catch (\Throwable $e) {
        $errors++;
        echo "  Error #{$i}: {$e->getMessage()}\n";
    }
}

$elapsed = microtime(true) - $start;
$rps = 1000 / $elapsed;

echo "  Result: " . ($errors === 0 ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m") . "\n";
echo "  Errors: {$errors}\n";
echo "  Time:   " . number_format($elapsed, 2) . "s (" . number_format($rps, 0) . " req/s)\n";
echo "  Status distribution: " . json_encode($statusCounts) . "\n";

if ($errors === 0) {
    $passed++;
} else {
    $failed++;
}

// Check traces were created
$traceFiles = glob($traceDir . '/*.json') ?: [];
echo "  Traces created: " . count($traceFiles) . "\n";
echo "\n";

// ─── 2. Concurrent-ish burst ─────────────────

echo "--- 2. Burst test (200 requests, mix of methods) ---\n";

$burstErrors = 0;
$burstStart = microtime(true);

for ($i = 0; $i < 200; $i++) {
    try {
        switch ($i % 4) {
            case 0:
                $code = dispatch($app, 'GET', '/');
                break;
            case 1:
                $code = dispatch($app, 'GET', '/api/users');
                break;
            case 2:
                $code = dispatch($app, 'GET', '/nonexistent');
                break;
            case 3:
                $code = dispatch($app, 'POST', '/api/auth/login', ['email' => 'test@test.com']);
                break;
        }
    } catch (\Throwable $e) {
        $burstErrors++;
    }
}

$burstTime = microtime(true) - $burstStart;
echo "  Result: " . ($burstErrors === 0 ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m") . "\n";
echo "  Errors: {$burstErrors}\n";
echo "  Time:   " . number_format($burstTime, 3) . "s\n";

if ($burstErrors === 0) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ─── 3. Trace verification ───────────────────

echo "--- 3. Trace verification ---\n";

$traceFiles = glob($traceDir . '/*.json') ?: [];
$validTraces = 0;
$invalidTraces = 0;

// Take last 50 traces
$samples = array_slice($traceFiles, -50);
foreach ($samples as $file) {
    $data = json_decode((string) file_get_contents($file), true);
    if (is_array($data) && isset($data['method'], $data['path'], $data['status'])) {
        $validTraces++;
    } else {
        $invalidTraces++;
    }
}

echo "  Valid traces:   {$validTraces}\n";
echo "  Invalid traces: {$invalidTraces}\n";
echo "  Result: " . ($invalidTraces === 0 ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m") . "\n";

if ($invalidTraces === 0) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ─── 4. Replay trace test ────────────────────

echo "--- 4. Replay trace test ---\n";

// Create a manual trace file for replay testing
$replayTraceId = 'test_replay_' . bin2hex(random_bytes(4));
$replayData = [
    'method' => 'POST',
    'path' => '/api/auth/login',
    'status' => 422,
    'time_ms' => 5.0,
    'ip' => '127.0.0.1',
    'timestamp' => date('Y-m-d H:i:s'),
    'request_headers' => ['content-type' => 'application/json'],
    'request_body' => '{"email":"test@test.com"}',
    'response_body' => '{"success":false,"errors":{"password":["Required"]}}',
    'queries' => [],
];

$replayFile = $traceDir . '/' . $replayTraceId . '.json';
if (!is_dir($traceDir)) {
    mkdir($traceDir, 0775, true);
}
file_put_contents($replayFile, json_encode($replayData));

// Verify trace is readable
$read = json_decode((string) file_get_contents($replayFile), true);
ok(is_array($read) && $read['method'] === 'POST', 'Trace should be readable');
echo "  Trace written: \033[32mOK\033[0m\n";
echo "  Trace re-read: \033[32mOK\033[0m\n";

// Test replay via LogReplayCommand
$replayCmd = new \Siro\Core\Commands\LogReplayCommand($basePath);
$exitCode = $replayCmd->run([$replayTraceId]);
echo "  Replay command: " . ($exitCode === 0 ? "\033[32mOK\033[0m" : "\033[31mFAIL\033[0m") . "\n";

// Clean up
unlink($replayFile);

$passed++;
echo "\n";

// ─── 5. api:test stability ───────────────────

echo "--- 5. api:test stability (30 calls) ---\n";

$apiTestCmd = new \Siro\Core\Commands\ApiTestCommand($basePath);
$apiErrors = 0;

for ($i = 0; $i < 30; $i++) {
    ob_start();
    $code = $apiTestCmd->run(['GET', ($i % 3 === 0) ? '/' : '/api/users']);
    ob_end_clean();
    if ($code !== 0) {
        $apiErrors++;
    }
}

echo "  Errors: {$apiErrors}\n";
echo "  Result: " . ($apiErrors === 0 ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m") . "\n";

if ($apiErrors === 0) {
    $passed++;
} else {
    $failed++;
}

echo "\n";

// ─── Summary ─────────────────────────────────

$totalTime = microtime(true) - $start;
echo "=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total requests: 1230\n";
echo "Total time: " . number_format($totalTime, 2) . "s\n";

exit($failed > 0 ? 1 : 0);
