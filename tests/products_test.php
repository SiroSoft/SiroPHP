<?php

declare(strict_types=1);

/**
 * Integration test for Product API.
 *
 * Run: php tests/products_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;

$basePath = dirname(__DIR__);

Router::setMiddlewareAliases([
    'auth' => \App\Middleware\AuthMiddleware::class,
    'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
    'cors' => \App\Middleware\CorsMiddleware::class,
    'json' => \App\Middleware\JsonMiddleware::class,
]);

$app = new App($basePath);
$app->boot();
$app->loadRoutes($basePath . '/routes/api.php');

echo "=== Product API Test ===

";

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  \033[32m✓\033[0m {$name}
";
        $passed++;
    } catch (\Throwable $e) {
        echo "  \033[31m✗ {$name}: {$e->getMessage()}\033[0m
";
        echo "    File: {$e->getFile()}:{$e->getLine()}
";
        $failed++;
    }
}

function dispatch(string $method, string $path, array $body = [], array $headers = []): array
{
    global $app;
    ob_start();
    try {
        $request = new Request($method, $path, [], $headers, $body, '127.0.0.1');
        $response = $app->router->dispatch($request);
        ob_end_clean();
        return [
            'status' => $response->statusCode(),
            'body' => json_decode(json_encode($response->payload()), true),
        ];
    } catch (\Siro\Core\ValidationException $e) {
        ob_end_clean();
        $response = $e->toResponse();
        return [
            'status' => $response->statusCode(),
            'body' => json_decode(json_encode($response->payload()), true),
        ];
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

// ─── Tests ──────────────────────────────────────────

test('GET /api/products returns list', function () {
    $res = dispatch('GET', '/api/products');
    assert($res['status'] === 200, 'Expected 200, got ' . $res['status']);
});

test('GET /api/products/999 returns 404', function () {
    $res = dispatch('GET', '/api/products/999');
    assert($res['status'] === 404, 'Expected 404, got ' . $res['status']);
    assert($res['body']['success'] === false, 'Expected success=false');
});

test('POST /api/products with valid data', function () {
    $res = dispatch('POST', '/api/products', ['name' => 'Test products']);
    assert($res['status'] === 201, 'Expected 201, got ' . $res['status']);
    assert($res['body']['success'] === true, 'Expected success=true');
});

test('POST /api/products without name returns 422', function () {
    $res = dispatch('POST', '/api/products', []);
    assert($res['status'] === 422, 'Expected 422, got ' . $res['status']);
});

echo "
=== Results ===
";
echo "Passed: {$passed}
";
echo "Failed: {$failed}
";
exit($failed > 0 ? 1 : 0);
