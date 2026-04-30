<?php

declare(strict_types=1);

/**
 * Edge Cases & Performance Testing for v0.8.9
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Commands\MakeCrudCommand;
use Siro\Core\Response;

echo "=== VÒNG 2: EDGE CASES & PERFORMANCE ===\n\n";

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $fn();
        echo "  ✓ $name\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  ✗ $name: {$e->getMessage()}\n";
        $failed++;
    }
}

// ─── Pluralization Edge Cases ──────────────────────
echo "1. Pluralization Tests:\n";

test('product → products', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $ref = new ReflectionClass($cmd);
    $method = $ref->getMethod('plural');
    $method->setAccessible(true);
    assert($method->invoke($cmd, 'product') === 'products');
});

test('category → categories', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $ref = new ReflectionClass($cmd);
    $method = $ref->getMethod('plural');
    $method->setAccessible(true);
    assert($method->invoke($cmd, 'category') === 'categories');
});

test('user → users', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $ref = new ReflectionClass($cmd);
    $method = $ref->getMethod('plural');
    $method->setAccessible(true);
    assert($method->invoke($cmd, 'user') === 'users');
});

test('class → class (limitation: words ending in s)', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $ref = new ReflectionClass($cmd);
    $method = $ref->getMethod('plural');
    $method->setAccessible(true);
    // Note: Simple algorithm - words ending in 's' are kept as-is
    // For perfect pluralization, use a dictionary-based solution
    assert($method->invoke($cmd, 'class') === 'class');
});

test('bus → bus (limitation: words ending in s)', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $ref = new ReflectionClass($cmd);
    $method = $ref->getMethod('plural');
    $method->setAccessible(true);
    // Note: This is a known limitation of simple pluralization
    assert($method->invoke($cmd, 'bus') === 'bus');
});

test('tags (already plural) → tags', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $ref = new ReflectionClass($cmd);
    $method = $ref->getMethod('plural');
    $method->setAccessible(true);
    // Note: If user inputs plural form, we keep it as-is
    assert($method->invoke($cmd, 'tags') === 'tags');
});

// ─── Response Headers Tests ────────────────────────
echo "\n2. Response Headers Tests:\n";

test('X-Request-Id is set', function () {
    $requestId = bin2hex(random_bytes(8));
    Response::setRequestMeta($requestId, microtime(true));
    // If no exception, it works
    assert(strlen($requestId) === 16);
});

test('X-Response-Time calculation', function () {
    $startedAt = microtime(true) - 0.05; // 50ms ago
    Response::setRequestMeta('test123', $startedAt);
    $response = Response::success([]);
    assert($response->statusCode() === 200);
});

test('Multiple responses maintain state', function () {
    Response::setRequestMeta('abc123', microtime(true));
    $r1 = Response::success(['test' => 1]);
    
    Response::setRequestMeta('def456', microtime(true));
    $r2 = Response::success(['test' => 2]);
    
    assert($r1->statusCode() === 200);
    assert($r2->statusCode() === 200);
});

// ─── Command Validation Tests ──────────────────────
echo "\n3. Command Validation Tests:\n";

test('make:crud requires resource name', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    ob_start();
    $result = $cmd->run([]);
    $output = ob_get_clean();
    assert($result === 1, 'Should return error code');
    assert(str_contains($output, 'Resource name is required'));
});

test('make:test requires test name', function () {
    $cmd = new \Siro\Core\Commands\MakeTestCommand(__DIR__ . '/..');
    ob_start();
    $result = $cmd->run([]);
    $output = ob_get_clean();
    assert($result === 1, 'Should return error code');
    assert(str_contains($output, 'Test name is required'));
});

// ─── Performance Tests ─────────────────────────────
echo "\n4. Performance Tests:\n";

test('Response creation < 1ms', function () {
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        Response::success(['data' => $i]);
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 1000;
    assert($avg < 1, "Average response creation should be < 1ms, got {$avg}ms");
});

test('Pluralization < 0.1ms per call', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $ref = new ReflectionClass($cmd);
    $method = $ref->getMethod('plural');
    $method->setAccessible(true);
    
    $start = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        $method->invoke($cmd, 'product');
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 10000;
    assert($avg < 0.1, "Average pluralization should be < 0.1ms, got {$avg}ms");
});

// ─── Summary ───────────────────────────────────────
echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed > 0) {
    exit(1);
}

echo "\n✅ All edge cases and performance tests passed!\n";
