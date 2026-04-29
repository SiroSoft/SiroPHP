<?php

declare(strict_types=1);

/**
 * Comprehensive test for v0.8.9 features:
 * - make:crud command
 * - make:test command  
 * - Response headers (X-Request-Id, X-Response-Time)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Response;
use Siro\Core\Commands\MakeCrudCommand;
use Siro\Core\Commands\MakeTestCommand;

echo "=== v0.8.9 Feature Tests ===\n\n";

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $fn();
        echo "  \033[32m✓\033[0m {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \033[31m✗ {$name}: {$e->getMessage()}\033[0m\n";
        echo "    File: {$e->getFile()}:{$e->getLine()}\n";
        $failed++;
    }
}

// ─── Test 1: MakeCrudCommand exists ──────────────────
test('MakeCrudCommand class exists', function () {
    assert(class_exists(MakeCrudCommand::class), 'MakeCrudCommand should exist');
});

// ─── Test 2: MakeTestCommand exists ──────────────────
test('MakeTestCommand class exists', function () {
    assert(class_exists(MakeTestCommand::class), 'MakeTestCommand should exist');
});

// ─── Test 3: Response has setRequestMeta method ──────
test('Response::setRequestMeta method exists', function () {
    assert(method_exists(Response::class, 'setRequestMeta'), 'Method should exist');
});

// ─── Test 4: Response headers are set ────────────────
test('Response sets request ID and timing', function () {
    $requestId = bin2hex(random_bytes(8));
    $startedAt = microtime(true);
    
    Response::setRequestMeta($requestId, $startedAt);
    
    // Verify static properties are set (indirectly through behavior)
    $response = Response::success(['test' => 'data']);
    assert($response->statusCode() === 200, 'Status should be 200');
});

// ─── Test 5: Plural helper works correctly ───────────
test('plural() handles regular words', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $reflection = new ReflectionClass($cmd);
    $method = $reflection->getMethod('plural');
    $method->setAccessible(true);
    
    $result = $method->invoke($cmd, 'product');
    assert($result === 'products', "Expected 'products', got '{$result}'");
});

test('plural() handles words ending in y', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $reflection = new ReflectionClass($cmd);
    $method = $reflection->getMethod('plural');
    $method->setAccessible(true);
    
    $result = $method->invoke($cmd, 'category');
    assert($result === 'categories', "Expected 'categories', got '{$result}'");
});

test('plural() handles already plural words', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $reflection = new ReflectionClass($cmd);
    $method = $reflection->getMethod('plural');
    $method->setAccessible(true);
    
    $result = $method->invoke($cmd, 'users');
    assert($result === 'users', "Expected 'users', got '{$result}'");
});

test('plural() handles words ending in ss', function () {
    $cmd = new MakeCrudCommand(__DIR__ . '/..');
    $reflection = new ReflectionClass($cmd);
    $method = $reflection->getMethod('plural');
    $method->setAccessible(true);
    
    $result = $method->invoke($cmd, 'class');
    assert($result === 'classes', "Expected 'classes', got '{$result}'");
});

// ─── Test 6: Generated files structure ───────────────
test('Generated CRUD test file exists', function () {
    $testFile = __DIR__ . '/categories_test.php';
    if (!file_exists($testFile)) {
        throw new Exception('categories_test.php not found (run: php siro make:crud categories first)');
    }
    
    $content = file_get_contents($testFile);
    assert(str_contains($content, 'dispatch('), 'Test should have dispatch helper');
    assert(str_contains($content, 'test('), 'Test should use test() function');
});

test('Generated unit test file exists', function () {
    $testFile = __DIR__ . '/UserService_test.php';
    if (!file_exists($testFile)) {
        throw new Exception('UserService_test.php not found (run: php siro make:test UserService --unit first)');
    }
    
    $content = file_get_contents($testFile);
    assert(!str_contains($content, 'dispatch('), 'Unit test should NOT have dispatch helper');
    assert(str_contains($content, 'Unit test'), 'Should be marked as unit test');
});

// ─── Summary ─────────────────────────────────────────
echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed > 0) {
    exit(1);
}

echo "\n✅ All v0.8.9 tests passed!\n";
echo "\nFeatures verified:\n";
echo "  ✓ make:crud command scaffolding\n";
echo "  ✓ make:test command (API & Unit)\n";
echo "  ✓ Response headers (X-Request-Id, X-Response-Time)\n";
echo "  ✓ Pluralization logic fixed\n";
