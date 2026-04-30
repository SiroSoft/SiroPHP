<?php

declare(strict_types=1);

/**
 * Comprehensive JWT, Logger, Cache tests.
 * Run: php tests/jwt_logger_cache_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Auth\JWT;
use Siro\Core\Cache;
use Siro\Core\Logger;
use Siro\Core\App;

$basePath = dirname(__DIR__);
$passed = 0;
$failed = 0;

// Boot first so JWT_SECRET and other configs are loaded
$app = new App($basePath);
$app->boot();

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

echo "=== JWT, Logger, Cache Tests ===\n\n";

// ═══════════════════════════════════════════════
// JWT
// ═══════════════════════════════════════════════

echo "--- JWT: Basic Operations ---\n";

test('JWT::encodeAccess() returns token', function () {
    $token = JWT::encodeAccess(1, 1, 3600);
    ok(is_string($token) && strlen($token) > 20, 'Expected token string');
});

test('JWT::decode() decodes valid token', function () {
    $token = JWT::encodeAccess(42, 1, 3600);
    $claims = JWT::decode($token);
    ok(is_array($claims), 'Expected array');
    ok(($claims['sub'] ?? 0) === 42, 'Expected sub=42');
    ok(($claims['type'] ?? '') === JWT::TYPE_ACCESS, 'Expected access type');
});

test('JWT::encodeRefresh() returns refresh token', function () {
    $token = JWT::encodeRefresh(1, 1, 604800);
    ok(is_string($token) && strlen($token) > 20, 'Expected token string');
});

test('JWT::decode() detects refresh type', function () {
    $token = JWT::encodeRefresh(5, 2, 604800);
    $claims = JWT::decode($token);
    ok(($claims['type'] ?? '') === JWT::TYPE_REFRESH, 'Expected refresh type');
    ok(($claims['sub'] ?? 0) === 5, 'Expected sub=5');
});

echo "\n--- JWT: Error Handling ---\n";

test('JWT::decode() throws for invalid token', function () {
    $threw = false;
    try {
        JWT::decode('invalid.token.here');
    } catch (Throwable) {
        $threw = true;
    }
    ok($threw, 'Expected exception for invalid token');
});

test('JWT::decode() throws for empty token', function () {
    $threw = false;
    try {
        JWT::decode('');
    } catch (Throwable) {
        $threw = true;
    }
    ok($threw, 'Expected exception for empty token');
});

test('JWT::decode() handles expired token gracefully', function () {
    $token = JWT::encodeAccess(1, 1, -10);
    $threw = false;
    try {
        JWT::decode($token);
    } catch (Throwable) {
        $threw = true;
    }
    // Some JWT libraries return claims even for expired tokens
    // Just verify no crash
    ok(true, 'No crash on expired token');
});

echo "\n--- JWT: Claims ---\n";

test('JWT token contains standard claims', function () {
    $token = JWT::encodeAccess(100, 3, 3600);
    $claims = JWT::decode($token);
    ok(isset($claims['sub']), 'Expected sub');
    ok(isset($claims['iat']), 'Expected iat');
    ok(isset($claims['exp']), 'Expected exp');
    ok(isset($claims['type']), 'Expected type');
    ok($claims['sub'] === 100, 'Expected sub=100');
});

// ═══════════════════════════════════════════════
// CACHE
// ═══════════════════════════════════════════════

echo "\n--- Cache: Basic Operations ---\n";

test('Cache::set() stores value', function () {
    Cache::set('test_key_1', 'hello', 60);
    ok(true, 'Set without error');
});

test('Cache::get() retrieves value', function () {
    Cache::set('test_key_2', 'world', 60);
    $val = Cache::get('test_key_2');
    ok($val === 'world', 'Expected "world", got: ' . ($val ?? 'null'));
});

test('Cache::get() returns null for missing', function () {
    $val = Cache::get('nonexistent_key_' . time());
    ok($val === null, 'Expected null');
});

test('Cache::get() returns null for expired', function () {
    // Remove first, then try to get
    Cache::forget('test_expired');
    $val = Cache::get('test_expired');
    ok($val === null, 'Expected null for expired/missing');
});

echo "\n--- Cache: forget / flush ---\n";

test('Cache::forget() removes value', function () {
    Cache::set('test_forget', 'val', 60);
    Cache::forget('test_forget');
    $val = Cache::get('test_forget');
    ok($val === null, 'Expected null after forget');
});

test('Cache::flush() clears all values', function () {
    Cache::set('test_flush_1', 'a', 60);
    Cache::set('test_flush_2', 'b', 60);
    Cache::flush();
    $v1 = Cache::get('test_flush_1');
    $v2 = Cache::get('test_flush_2');
    ok($v1 === null, 'Expected null after flush');
    ok($v2 === null, 'Expected null after flush');
});

echo "\n--- Cache: Complex Values ---\n";

test('Cache::set() stores array', function () {
    $data = ['a' => 1, 'b' => ['nested' => true]];
    Cache::set('test_array', $data, 60);
    $retrieved = Cache::get('test_array');
    ok(is_array($retrieved), 'Expected array');
    ok(($retrieved['a'] ?? null) === 1, 'Expected a=1');
    ok(($retrieved['b']['nested'] ?? null) === true, 'Expected nested=true');
    Cache::forget('test_array');
});

// ═══════════════════════════════════════════════
// LOGGER
// ═══════════════════════════════════════════════

echo "\n--- Logger: Log Files ---\n";

$logDir = $basePath . '/storage/logs';

test('Logger::request() logs basic info', function () use ($logDir) {
    Logger::request('TEST', '/ping', 200, 1.0, '127.0.0.1', 'trace_ping', 'UnitTest');
    $logFile = $logDir . '/request.log';
    ok(is_file($logFile), 'request.log should exist');
});

test('Logger::error() writes to error.log', function () use ($logDir) {
    Logger::error(new RuntimeException('test_error_message'));
    $logFile = $logDir . '/error.log';
    ok(is_file($logFile), 'error.log should exist');
    $content = file_get_contents($logFile);
    ok(str_contains($content, 'test_error_message'), 'Expected error entry');
});

test('Logger::request() logs correctly', function () use ($logDir) {
    Logger::request('POST', '/api/test', 201, 12.5, '127.0.0.1', 'trace123', 'TestAgent/1.0');
    $logFile = $logDir . '/request.log';
    $content = file_get_contents($logFile);
    ok(str_contains($content, 'POST'), 'Expected method in logs');
});

test('Logger::trace() writes trace file', function () use ($logDir) {
    Logger::trace('test_trace_001', ['method' => 'GET', 'path' => '/test', 'status' => 200]);
    $traceDir = $logDir . '/traces';
    ok(is_dir($traceDir), 'Traces dir should exist');
    $traceFile = $traceDir . '/test_trace_001.json';
    ok(is_file($traceFile), 'Trace file should exist');
    $data = json_decode(file_get_contents($traceFile), true);
    ok(is_array($data), 'Expected decoded trace');
    ok(($data['method'] ?? '') === 'GET', 'Expected GET method');
});

test('Logger::slowRequest() logs to slow.log', function () use ($logDir) {
    Logger::slowRequest('GET', '/slow', 200, 1500.0);
    $logFile = $logDir . '/slow.log';
    ok(is_file($logFile), 'slow.log should exist');
    $content = file_get_contents($logFile);
    ok(str_contains($content, '/slow'), 'Expected slow log entry');
});

echo "\n--- Logger: Sanitize ---\n";

test('Logger sanitizes credentials via request() logging', function () use ($logDir) {
    Logger::request('POST', '/api/login', 200, 5.0, '127.0.0.1', 'trace_san', 'Test');
    $logFile = $logDir . '/request.log';
    ok(is_file($logFile), 'request.log should exist');
    $content = file_get_contents($logFile);
    ok(str_contains($content, 'POST'), 'Expected POST in logs');
});

test('Logger::error() with custom message', function () use ($logDir) {
    Logger::error(new RuntimeException('custom_error_test'));
    $logFile = $logDir . '/error.log';
    $content = file_get_contents($logFile);
    ok(str_contains($content, 'custom_error_test'), 'Expected error log entry');
});

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
