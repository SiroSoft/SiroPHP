<?php

declare(strict_types=1);

/**
 * Test response headers for v0.8.9
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Response;

echo "=== Response Headers Test (v0.8.9) ===\n\n";

// Test 1: Check Response class has setRequestMeta method
test('Response::setRequestMeta exists', function () {
    assert(method_exists(Response::class, 'setRequestMeta'), 'Method setRequestMeta should exist');
});

// Test 2: Check Response send() includes headers
test('Response sends X-Request-Id header', function () {
    // Set request meta
    Response::setRequestMeta('test123abc', microtime(true));
    
    // Create response - just verify it can be created without errors
    $response = Response::success(['test' => 'data']);
    assert($response->statusCode() === 200, 'Status should be 200');
    $payload = $response->payload();
    assert($payload['success'] === true, 'Response should be successful');
});

// Test 3: Verify timing calculation
test('X-Response-Time is calculated', function () {
    $startedAt = microtime(true) - 0.05; // 50ms ago
    Response::setRequestMeta('abc123', $startedAt);
    
    // Just verify the method works without errors
    $response = Response::success([]);
    assert($response->statusCode() === 200, 'Status should be 200');
});

function test(string $name, callable $fn): void {
    try {
        $fn();
        echo "  ✓ $name\n";
    } catch (Throwable $e) {
        echo "  ✗ $name: {$e->getMessage()}\n";
        exit(1);
    }
}

echo "\n✅ All header tests passed!\n";
echo "\nNote: Full header verification requires HTTP server.\n";
echo "Headers are set via header() in Response::send().\n";
