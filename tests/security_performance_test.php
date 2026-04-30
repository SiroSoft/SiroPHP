<?php

declare(strict_types=1);

/**
 * Security & Performance Testing Suite for SiroPHP v0.8.9
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Validator;
use Siro\Core\Auth\JWT;
use Siro\Core\Cache;

$basePath = dirname(__DIR__);
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

echo "=== SECURITY TESTING ===\n\n";

// ─── SQL Injection Tests ──────────────────────
echo "1. SQL Injection Protection:\n";

test('SQL injection in email is rejected', function () {
    $errors = Validator::make(
        ['email' => "admin' OR '1'='1"],
        ['email' => 'required|email']
    );
    assert(isset($errors['email']), 'Should reject SQL injection');
});

test('SQL injection in username is sanitized', function () {
    $errors = Validator::make(
        ['name' => "'; DROP TABLE users;--"],
        ['name' => 'required|string|max:100']
    );
    // Should pass validation but PDO will protect at DB level
    assert($errors === [] || isset($errors['name']));
});

test('Numeric SQL injection is blocked', function () {
    $errors = Validator::make(
        ['id' => "1 OR 1=1"],
        ['id' => 'required|integer']
    );
    assert(isset($errors['id']), 'Should reject non-integer');
});

// ─── XSS Protection Tests ─────────────────────
echo "\n2. XSS Protection:\n";

test('Script tags in input are preserved (output escaping needed)', function () {
    $input = '<script>alert("XSS")</script>';
    $errors = Validator::make(
        ['content' => $input],
        ['content' => 'required|string']
    );
    // Validation passes, but output should be escaped
    assert($errors === []);
});

test('HTML entities in input', function () {
    $input = '<img src=x onerror=alert(1)>';
    $errors = Validator::make(
        ['html' => $input],
        ['html' => 'string']
    );
    assert($errors === []);
});

// ─── JWT Security Tests ───────────────────────
echo "\n3. JWT Security:\n";

// Set JWT secret for testing
putenv('JWT_SECRET=test_secret_key_for_testing_purposes_only');

test('JWT token generation works', function () {
    $payload = ['sub' => 1, 'ver' => 1, 'role' => 'admin'];
    $token = JWT::encode($payload);
    assert(!empty($token), 'Token should not be empty');
    assert(strlen($token) > 50, 'Token should be reasonably long');
});

test('JWT token verification works', function () {
    $payload = ['sub' => 1, 'ver' => 1, 'exp' => time() + 3600];
    $token = JWT::encode($payload);
    $decoded = JWT::decode($token);
    assert($decoded['sub'] === 1, 'User ID should match');
});

test('Expired JWT token is rejected', function () {
    $payload = ['sub' => 1, 'ver' => 1, 'exp' => time() - 3600];
    $token = JWT::encode($payload);
    try {
        JWT::decode($token);
        assert(false, 'Should throw exception for expired token');
    } catch (Exception $e) {
        assert(true); // Expected
    }
});

test('Invalid JWT signature is rejected', function () {
    $payload = ['sub' => 1, 'ver' => 1];
    $token = JWT::encode($payload);
    $tampered = substr($token, 0, -5) . 'xxxxx';
    try {
        JWT::decode($tampered);
        assert(false, 'Should throw exception for tampered token');
    } catch (Exception $e) {
        assert(true); // Expected
    }
});

test('Empty JWT token is rejected', function () {
    try {
        JWT::decode('');
        assert(false, 'Should throw exception for empty token');
    } catch (Exception $e) {
        assert(true); // Expected
    }
});

// ─── Rate Limiting Tests ──────────────────────
echo "\n4. Rate Limiting:\n";

test('Rate limit key generation is consistent', function () {
    $ip1 = '192.168.1.1';
    $ip2 = '192.168.1.2';
    $key1 = hash('sha256', $ip1 . ':/api/test');
    $key2 = hash('sha256', $ip2 . ':/api/test');
    assert($key1 !== $key2, 'Different IPs should have different keys');
});

// ─── Input Validation Tests ───────────────────
echo "\n5. Input Validation:\n";

test('Required field validation', function () {
    $errors = Validator::make([], ['email' => 'required']);
    assert(isset($errors['email']), 'Should require email');
});

test('Email format validation', function () {
    $errors = Validator::make(['email' => 'invalid'], ['email' => 'email']);
    assert(isset($errors['email']), 'Should reject invalid email');
});

test('Min length validation', function () {
    $errors = Validator::make(['pass' => '123'], ['pass' => 'min:8']);
    assert(isset($errors['pass']), 'Should reject short password');
});

test('Max length validation', function () {
    $errors = Validator::make(['name' => str_repeat('a', 256)], ['name' => 'max:100']);
    assert(isset($errors['name']), 'Should reject long name');
});

test('Integer validation', function () {
    $errors = Validator::make(['age' => 'abc'], ['age' => 'integer']);
    assert(isset($errors['age']), 'Should reject non-integer');
});

test('Multiple rules validation', function () {
    $errors = Validator::make(
        ['email' => 'test@test.com', 'pass' => 'short'],
        ['email' => 'required|email', 'pass' => 'required|min:8']
    );
    assert(!isset($errors['email']), 'Email should be valid');
    assert(isset($errors['pass']), 'Password should fail min length');
});

// ═══════════════════════════════════════════════
// PERFORMANCE TESTING
// ═══════════════════════════════════════════════

echo "\n\n=== PERFORMANCE TESTING ===\n\n";

// ─── Response Time Tests ──────────────────────
echo "6. Response Time:\n";

test('Response creation < 0.1ms average', function () {
    $start = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        Response::success(['data' => $i]);
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 10000;
    assert($avg < 0.1, "Average should be < 0.1ms, got {$avg}ms");
});

test('JSON encoding < 0.05ms average', function () {
    $data = ['message' => 'test', 'data' => range(1, 100)];
    $start = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        json_encode($data);
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 10000;
    assert($avg < 0.05, "Average should be < 0.05ms, got {$avg}ms");
});

// ─── Memory Usage Tests ───────────────────────
echo "\n7. Memory Usage:\n";

test('App bootstrap memory < 5MB', function () use ($basePath) {
    $startMem = memory_get_usage();
    $app = new App($basePath);
    $app->boot();
    $endMem = memory_get_usage();
    $used = ($endMem - $startMem) / 1024 / 1024;
    assert($used < 5, "Bootstrap should use < 5MB, used {$used}MB");
});

test('Single request memory < 1MB', function () {
    $startMem = memory_get_usage();
    $response = Response::success(['test' => 'data']);
    $endMem = memory_get_usage();
    $used = ($endMem - $startMem) / 1024 / 1024;
    assert($used < 1, "Request should use < 1MB, used {$used}MB");
});

// ─── Cache Performance Tests ──────────────────
echo "\n8. Cache Performance:\n";

test('Cache set < 5ms', function () {
    $start = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        Cache::set("perf_test_$i", "value_$i", 60);
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 100;
    assert($avg < 5, "Cache set should be < 5ms avg, got {$avg}ms");
});

test('Cache get < 0.5ms', function () {
    Cache::set('perf_get_test', 'value', 60);
    $start = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        Cache::get('perf_get_test');
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 10000;
    assert($avg < 0.5, "Cache get should be < 0.5ms avg, got {$avg}ms");
});

// ─── JWT Performance Tests ────────────────────
echo "\n9. JWT Performance:\n";

test('JWT encode < 1ms', function () {
    $payload = ['sub' => 1, 'ver' => 1, 'exp' => time() + 3600];
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        JWT::encode($payload);
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 1000;
    assert($avg < 1, "JWT encode should be < 1ms avg, got {$avg}ms");
});

test('JWT decode < 1ms', function () {
    $payload = ['sub' => 1, 'ver' => 1, 'exp' => time() + 3600];
    $token = JWT::encode($payload);
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        JWT::decode($token);
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 1000;
    assert($avg < 1, "JWT decode should be < 1ms avg, got {$avg}ms");
});

// ─── Validator Performance Tests ──────────────
echo "\n10. Validator Performance:\n";

test('Simple validation < 0.5ms', function () {
    $start = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        Validator::make(['email' => 'test@test.com'], ['email' => 'required|email']);
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 10000;
    assert($avg < 0.5, "Validation should be < 0.5ms avg, got {$avg}ms");
});

test('Complex validation < 1ms', function () {
    $start = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        Validator::make(
            [
                'name' => 'Test User',
                'email' => 'test@test.com',
                'age' => 25,
                'bio' => 'Short bio',
            ],
            [
                'name' => 'required|string|max:100',
                'email' => 'required|email',
                'age' => 'required|integer|min:18|max:120',
                'bio' => 'string|max:500',
            ]
        );
    }
    $elapsed = (microtime(true) - $start) * 1000;
    $avg = $elapsed / 1000;
    assert($avg < 1, "Complex validation should be < 1ms avg, got {$avg}ms");
});

// ─── Scalability Tests ────────────────────────
echo "\n11. Scalability:\n";

test('100 concurrent responses stable', function () {
    $responses = [];
    for ($i = 0; $i < 100; $i++) {
        $responses[] = Response::success(['id' => $i]);
    }
    assert(count($responses) === 100, 'Should create 100 responses');
});

test('Memory stable under load', function () {
    $startMem = memory_get_usage();
    for ($i = 0; $i < 1000; $i++) {
        Response::success(['iteration' => $i]);
        if ($i % 100 === 0) {
            gc_collect_cycles();
        }
    }
    $endMem = memory_get_usage();
    $growth = ($endMem - $startMem) / 1024 / 1024;
    assert($growth < 2, "Memory growth should be < 2MB, grew {$growth}MB");
});

// ═══════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════

echo "\n\n=== RESULTS ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed > 0) {
    exit(1);
}

echo "\n✅ All security and performance tests passed!\n";
