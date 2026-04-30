<?php

/**
 * SiroPHP Performance Benchmark Suite.
 *
 * Measures request handling time, memory usage, and throughput.
 * Run: php tests/benchmark.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;

$basePath = dirname(__DIR__);

echo "========================================\n";
echo " SiroPHP Performance Benchmark\n";
echo "========================================\n\n";

// ─── Helpers ───────────────────────────────────

function bench(string $label, int $iterations, callable $fn): array
{
    // Warmup
    $fn();

    $start = microtime(true);
    $memStart = memory_get_usage();
    $peakStart = memory_get_peak_usage(true);

    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }

    $elapsed = microtime(true) - $start;
    $avgMs = ($elapsed / $iterations) * 1000;
    $memEnd = memory_get_usage();
    $peakEnd = memory_get_peak_usage(true);
    $memDelta = $memEnd - $memStart;
    $peakDelta = $peakEnd - $peakStart;
    $opsPerSec = $iterations / $elapsed;

    printf("  %-25s %6d iters  %7.2fms avg  %7.0f ops/s  %+6dKB mem\n",
        $label, $iterations, $avgMs, $opsPerSec, $memDelta / 1024);

    return ['avg_ms' => $avgMs, 'ops' => $opsPerSec, 'mem_kb' => $memDelta / 1024, 'peak_kb' => $peakDelta / 1024];
}

function dispatch(App $app, string $method, string $path, array $body = []): Response
{
    $request = new Request($method, $path, [], ['accept' => 'application/json'], $body, '127.0.0.1');
    ob_start();
    try {
        $response = $app->router->dispatch($request);
        ob_end_clean();
        return $response;
    } catch (\Siro\Core\ValidationException $e) {
        ob_end_clean();
        return $e->toResponse();
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

function box(string $label, string $value): void
{
    printf("  \033[1;33m%s\033[0m  %s\n", str_pad($label, 18), $value);
}

// ─── Benchmark Suite ───────────────────────────

echo "--- Environment ---\n";
box('PHP Version', PHP_VERSION);
box('Platform', PHP_OS . ' / "win32"');
box('Memory Limit', ini_get('memory_limit'));
echo "\n";

// Boot app once
$app = new App($basePath);
$app->boot();
$app->loadRoutes($basePath . '/routes/api.php');

$results = [];

echo "--- Cold Boot (first request) ---\n";
$results['boot'] = bench('App boot + dispatch', 1, function () use ($basePath) {
    $a = new App($basePath);
    $a->boot();
    $a->loadRoutes($basePath . '/routes/api.php');
    $req = new Request('GET', '/', [], ['accept' => 'application/json'], [], '127.0.0.1');
    $a->router->dispatch($req);
});

echo "\n--- Warm Requests ---\n";
$results['root'] = bench('GET / (root)', 500, function () use ($app) {
    dispatch($app, 'GET', '/');
});

$results['notfound'] = bench('GET /nonexistent', 1000, function () use ($app) {
    dispatch($app, 'GET', '/nonexistent');
});

$results['login_422'] = bench('POST /auth/login (422)', 1000, function () use ($app) {
    dispatch($app, 'POST', '/api/auth/login', ['email' => 'test@test.com']);
});

$results['register_422'] = bench('POST /auth/register (422)', 1000, function () use ($app) {
    dispatch($app, 'POST', '/api/auth/register', []);
});

echo "\n--- Router Performance ---\n";
$pureRouter = new Router();
$pureRouter->get('/hello', fn () => Response::success(['msg' => 'hi']));
$pureRouter->get('/hello/{name}', fn () => Response::success());
$pureRouter->get('/users/{id}/posts/{postId}', fn () => Response::success());
$pureRouter->group('/api', [], function (Router $r): void {
    $r->get('/v1/users', fn () => Response::success());
    $r->post('/v1/users', fn () => Response::success());
    $r->get('/v1/users/{id}', fn () => Response::success());
});

$results['static_route'] = bench('Static route match', 1000, function () use ($pureRouter) {
    $req = new Request('GET', '/hello', [], [], [], '127.0.0.1');
    $pureRouter->dispatch($req);
});

$results['param_route'] = bench('Param route match', 1000, function () use ($pureRouter) {
    $req = new Request('GET', '/hello/world', [], [], [], '127.0.0.1');
    $pureRouter->dispatch($req);
});

$results['multi_param'] = bench('Multi-param route', 1000, function () use ($pureRouter) {
    $req = new Request('GET', '/users/42/posts/99', [], [], [], '127.0.0.1');
    $pureRouter->dispatch($req);
});

$results['group_route'] = bench('Grouped route', 1000, function () use ($pureRouter) {
    $req = new Request('GET', '/api/v1/users', [], [], [], '127.0.0.1');
    $pureRouter->dispatch($req);
});

$results['notfound_bench'] = bench('404 miss', 1000, function () use ($pureRouter) {
    $req = new Request('GET', '/nonexistent/path', [], [], [], '127.0.0.1');
    $pureRouter->dispatch($req);
});

echo "\n--- Summary ---\n";
$allAvg = array_sum(array_column($results, 'avg_ms')) / count($results);
$allOps = array_sum(array_column($results, 'ops')) / count($results);
$minAvg = min(array_column($results, 'avg_ms'));
$maxAvg = max(array_column($results, 'avg_ms'));
$minOps = min(array_column($results, 'ops'));
$maxOps = max(array_column($results, 'ops'));

box('Average time', sprintf('%.2fms', $allAvg));
box('Fastest', sprintf('%.2fms', $minAvg));
box('Slowest', sprintf('%.2fms', $maxAvg));
box('Avg throughput', sprintf('%.0f ops/s', $allOps));
box('Best throughput', sprintf('%.0f ops/s', $maxOps));

echo "\n========================================\n";
echo " Benchmark Complete\n";
echo "========================================\n";
