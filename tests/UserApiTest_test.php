<?php

declare(strict_types=1);

/**
 * Integration test for UserApiTest.
 *
 * Run: php tests/UserApiTest_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;

$basePath = dirname(__DIR__);

$app = new App($basePath);
$app->boot();
$app->loadRoutes($basePath . '/routes/api.php');

echo "=== UserApiTest Test ===

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

// ─── Write your tests below ────────────────────────

test('TODO: write test name', function () {
    $res = dispatch('GET', '/api/example');
    assert($res['status'] === 200, 'Expected 200, got ' . $res['status']);
});

echo "
=== Results ===
";
echo "Passed: {$passed}
";
echo "Failed: {$failed}
";
exit($failed > 0 ? 1 : 0);
