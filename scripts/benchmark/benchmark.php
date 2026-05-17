#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * SiroPHP Core Benchmark
 * Run: php benchmark/benchmark.php
 * Requires: php siro serve running on port 8080
 */

const BASE_URL = 'http://localhost:8080';
const ITERATIONS = 100;
const WARMUP = 10;

class Bench
{
    private static array $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'cyan' => "\033[36m",
        'bold' => "\033[1m",
        'reset' => "\033[0m",
    ];

    public static function out(string $msg, string $color = ''): void
    {
        $c = self::$colors;
        $prefix = $color !== '' ? ($c[$color] ?? '') : '';
        echo $prefix . $msg . $c['reset'] . "\n";
    }

    public static function header(string $title): void
    {
        echo "\n" . self::$colors['bold'] . self::$colors['cyan']
            . "╔══════════════════════════════════════════════════╗\n"
            . "║  {$title}" . str_repeat(' ', max(0, 48 - strlen($title))) . "║\n"
            . "╚══════════════════════════════════════════════════╝\n"
            . self::$colors['reset'];
    }
}

function request(string $method, string $path, array $headers = []): array
{
    $url = BASE_URL . $path;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge(['Accept: application/json'], $headers),
    ]);

    $start = hrtime(true);
    $body = curl_exec($ch);
    $end = hrtime(true);

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'body' => $body,
        'time' => ($end - $start) / 1e6, // ms
        'error' => $error,
    ];
}

function benchmark(string $name, string $method, string $path, int $iterations = ITERATIONS): array
{
    // Warmup
    for ($i = 0; $i < WARMUP; $i++) {
        request($method, $path);
    }

    $times = [];
    $successes = 0;
    $failures = 0;

    for ($i = 0; $i < $iterations; $i++) {
        $result = request($method, $path);
        if ($result['status'] >= 200 && $result['status'] < 300 && $result['error'] === '') {
            $times[] = $result['time'];
            $successes++;
        } else {
            $failures++;
        }
    }

    if ($times === []) {
        return ['error' => 'All requests failed'];
    }

    $avg = array_sum($times) / count($times);
    sort($times);
    $p50 = $times[(int) (count($times) * 0.5)];
    $p95 = $times[(int) (count($times) * 0.95)];
    $p99 = $times[(int) (count($times) * 0.99)];

    return [
        'avg_ms' => round($avg, 3),
        'min_ms' => round(min($times), 3),
        'max_ms' => round(max($times), 3),
        'p50_ms' => round($p50, 3),
        'p95_ms' => round($p95, 3),
        'p99_ms' => round($p99, 3),
        'rps' => round(1000 / $avg, 1),
        'successes' => $successes,
        'failures' => $failures,
        'iterations' => count($times),
    ];
}

// --- Main ---

Bench::header('SiroPHP Performance Benchmark');
echo "\n";

$endpoints = [
    'GET /' => ['GET', '/'],
    'GET /health' => ['GET', '/health'],
    'GET /api/users' => ['GET', '/api/users'],
    'GET /api/products' => ['GET', '/api/products'],
    'GET /api/categories' => ['GET', '/api/categories'],
];

$results = [];

foreach ($endpoints as $name => [$method, $path]) {
    Bench::out("Testing: {$name}...", 'yellow');
    $result = benchmark($name, $method, $path, ITERATIONS);

    if (isset($result['error'])) {
        Bench::out("  ✗ Error: {$result['error']}", 'red');
        continue;
    }

    $results[$name] = $result;
    $statusColor = ($result['failures'] === 0) ? 'green' : 'red';
    Bench::out("  ✓ {$result['successes']}/{$result['iterations']} successful, {$result['failures']} failed", $statusColor);
    Bench::out("  Avg: {$result['avg_ms']}ms | Min: {$result['min_ms']}ms | Max: {$result['max_ms']}ms");
    Bench::out("  P50: {$result['p50_ms']}ms | P95: {$result['p95_ms']}ms | P99: {$result['p99_ms']}ms");
    Bench::out("  Throughput: {$result['rps']} req/s");
}

if ($results === []) {
    Bench::out("\n No results. Make sure the dev server is running: php siro serve --port=8080", 'red');
    exit(1);
}

// Summary table
Bench::header('Summary');

echo str_pad('Endpoint', 30) . str_pad('Avg(ms)', 12) . str_pad('Min(ms)', 12)
    . str_pad('Max(ms)', 12) . str_pad('Req/s', 10) . "\n";
echo str_repeat('─', 76) . "\n";

$totalAvg = 0;
$totalRps = 0;
foreach ($results as $name => $r) {
    echo str_pad(substr($name, 0, 28), 30)
        . str_pad((string) $r['avg_ms'], 12)
        . str_pad((string) $r['min_ms'], 12)
        . str_pad((string) $r['max_ms'], 12)
        . str_pad((string) $r['rps'], 10) . "\n";
    $totalAvg += $r['avg_ms'];
    $totalRps += $r['rps'];
}

$count = count($results);
echo str_repeat('─', 76) . "\n";
echo str_pad('Average', 30)
    . str_pad((string) round($totalAvg / $count, 3), 12)
    . str_pad('', 12)
    . str_pad('', 12)
    . str_pad((string) round($totalRps / $count, 1), 10) . "\n\n";

Bench::out("Hardware: " . PHP_OS . " | PHP " . PHP_VERSION, 'yellow');
Bench::out("Date: " . date('Y-m-d H:i:s'), 'yellow');
Bench::out("Iterations per endpoint: " . ITERATIONS . " (+ " . WARMUP . " warmup)", 'yellow');
echo "\n";
Bench::out("Benchmark Complete!", 'green');

