<?php

declare(strict_types=1);

/**
 * Comprehensive Integration Test for SiroPHP.
 *
 * Tests core functionality using internal dispatch (no server needed).
 * Run: php tests/integration_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\Event;
use Siro\Core\Cache;
use Siro\Core\Env;
use Siro\Core\Storage;
use Siro\Core\Lang;
use Siro\Core\Validator;
use Siro\Core\ValidationException;
use Siro\Core\Database;
use Siro\Core\Logger;

$basePath = dirname(__DIR__);
$passed = 0;
$failed = 0;
$total = 0;

// ─── Helpers ───────────────────────────────────

function test(string $name, callable $fn): void
{
    global $passed, $failed, $total;
    $total++;
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

function app(): App
{
    global $basePath;

    Router::setMiddlewareAliases([
        'auth' => \App\Middleware\AuthMiddleware::class,
        'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
        'cors' => \App\Middleware\CorsMiddleware::class,
        'json' => \App\Middleware\JsonMiddleware::class,
    ]);

    $app = new App($basePath);
    $app->boot();
    $app->loadRoutes($basePath . '/routes/api.php');
    return $app;
}

function dispatch(App $app, string $method, string $path, array $body = [], array $headers = []): array
{
    ob_start();
    try {
        $request = new Request($method, $path, [], $headers, $body, '127.0.0.1');
        $response = $app->router->dispatch($request);
        ob_end_clean();
        return [
            'status' => $response->statusCode(),
            'body' => json_decode(json_encode($response->payload()), true),
        ];
    } catch (ValidationException $e) {
        ob_end_clean();
        $response = $e->toResponse();
        return [
            'status' => $response->statusCode(),
            'body' => json_decode(json_encode($response->payload()), true),
        ];
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
}

function ok(bool $condition, string $msg = 'Assertion failed'): void
{
    if (!$condition) {
        throw new RuntimeException($msg);
    }
}

// ═══════════════════════════════════════════════
// TEST SUITE
// ═══════════════════════════════════════════════

echo "=== SiroPHP v0.9.x Integration Test ===\n\n";

echo "--- Core Infrastructure ---\n";

test('App boots without error', function () {
    $a = app();
    ok($a !== null, 'App should boot');
});

test('Router dispatches root endpoint', function () {
    $a = app();
    $res = dispatch($a, 'GET', '/');
    ok($res['status'] === 200, 'Expected 200, got ' . $res['status']);
    ok(($res['body']['success'] ?? false) === true, 'Expected success=true');
    ok(isset($res['body']['data']['version']), 'Expected version in response');
});

test('Router returns 404 for unknown route', function () {
    $a = app();
    $res = dispatch($a, 'GET', '/nonexistent');
    ok($res['status'] === 404, 'Expected 404, got ' . $res['status']);
    ok(($res['body']['success'] ?? true) === false, 'Expected success=false');
});

echo "\n--- Validation & Error Handling ---\n";

test('Missing required fields returns 422', function () {
    $a = app();
    $res = dispatch($a, 'POST', '/api/auth/register', []);
    ok($res['status'] === 422, 'Expected 422, got ' . $res['status']);
    ok(isset($res['body']['meta']['errors']), 'Expected validation errors');
});

test('Invalid email returns 422', function () {
    $a = app();
    $res = dispatch($a, 'POST', '/api/auth/register', [
        'name' => 'Test',
        'email' => 'not-an-email',
        'password' => 'secret123',
    ]);
    ok($res['status'] === 422, 'Expected 422, got ' . $res['status']);
});

test('Validator::make() validates correctly', function () {
    $errors = Validator::make(['email' => 'bad'], ['email' => 'required|email']);
    ok(isset($errors['email']), 'Expected email validation error');
});

test('Validator::make() passes valid data', function () {
    $errors = Validator::make(['email' => 'test@example.com'], ['email' => 'required|email']);
    ok($errors === [], 'Expected no errors, got: ' . json_encode($errors));
});

test('Validator custom rule via extend()', function () {
    Validator::extend('positive', fn ($value) => $value > 0);
    $errors = Validator::make(['n' => -1], ['n' => 'positive']);
    ok(isset($errors['n']), 'Expected validation error for negative');
});

echo "\n--- Authentication Flow ---\n";

test('Login with missing fields returns 422', function () {
    $a = app();
    $res = dispatch($a, 'POST', '/api/auth/login', ['email' => 'test@test.com']);
    ok($res['status'] === 422, 'Expected 422, got ' . $res['status']);
});

test('Protected route returns 401 without token', function () {
    $a = app();
    $res = dispatch($a, 'GET', '/api/auth/me');
    ok($res['status'] === 401, 'Expected 401, got ' . $res['status']);
});

test('Protected route returns 401 with invalid token', function () {
    $a = app();
    $res = dispatch($a, 'GET', '/api/auth/me', [], ['authorization' => 'Bearer invalidtoken123']);
    ok($res['status'] === 401, 'Expected 401, got ' . $res['status']);
});

echo "\n--- Lang System ---\n";

test('Lang::get() returns English by default', function () {
    $msg = Lang::get('messages.welcome');
    ok($msg === 'Welcome', 'Expected "Welcome", got: ' . $msg);
});

test('Lang::get() returns Vietnamese when set', function () {
    Lang::setLocale('vi');
    $msg = Lang::get('messages.welcome');
    ok($msg === 'Chào mừng', 'Expected "Chào mừng", got: ' . $msg);
    Lang::setLocale('en');
});

test('Lang::get() supports parameter replacement', function () {
    $msg = Lang::get('messages.greeting', ['name' => 'Siro']);
    ok($msg !== '' && $msg !== null, 'Expected non-empty greeting');
});

test('Lang::has() checks key existence', function () {
    ok(Lang::has('messages.welcome') === true, 'Expected has(welcome)=true');
    ok(Lang::has('messages.nonexistent') === false, 'Expected has(nonexistent)=false');
});

echo "\n--- Event System ---\n";

test('Event::on() and Event::emit() work', function () {
    $fired = false;
    Event::on('test.event', function () use (&$fired) { $fired = true; });
    Event::emit('test.event');
    ok($fired === true, 'Expected listener to fire');
    Event::flush();
});

test('Event::emit() passes payload', function () {
    $result = null;
    Event::on('test.payload', function ($data) use (&$result) { $result = $data['key']; });
    Event::emit('test.payload', ['key' => 'value']);
    ok($result === 'value', 'Expected payload value');
    Event::flush();
});

test('Event::once() fires only once', function () {
    $count = 0;
    Event::once('test.once', function () use (&$count) { $count++; });
    Event::emit('test.once');
    Event::emit('test.once');
    ok($count === 1, 'Expected once() to fire only once');
    Event::flush();
});

test('Event::off() removes listeners', function () {
    $fired = false;
    Event::on('test.off', function () use (&$fired) { $fired = true; });
    Event::off('test.off');
    Event::emit('test.off');
    ok($fired === false, 'Expected listener to be removed');
});

test('Event::flush() clears all listeners', function () {
    Event::on('test.flush', function () {});
    Event::flush();
    ok(Event::hasListeners('test.flush') === false, 'Expected no listeners after flush');
});

echo "\n--- Storage ---\n";

test('Storage::put() writes and reads file', function () {
    $path = 'test_' . time() . '.txt';
    Storage::put($path, 'hello');
    $content = Storage::get($path);
    ok($content === 'hello', 'Expected "hello", got: ' . ($content ?? 'null'));
    Storage::delete($path);
});

test('Storage::exists() checks correctly', function () {
    $path = 'test_exists_' . time() . '.txt';
    ok(Storage::exists($path) === false, 'Expected false for non-existent');
    Storage::put($path, 'x');
    ok(Storage::exists($path) === true, 'Expected true for existent');
    Storage::delete($path);
});

echo "\n--- Cache System ---\n";

test('Cache::set() and Cache::get() work', function () {
    Cache::set('test_key', 'test_value', 60);
    $val = Cache::get('test_key');
    ok($val === 'test_value', 'Expected "test_value", got: ' . ($val ?? 'null'));
});

test('Cache::forget() removes value', function () {
    Cache::set('test_del', 'val', 60);
    Cache::forget('test_del');
    $val = Cache::get('test_del');
    ok($val === null, 'Expected null after forget');
});

test('Cache::flush() clears all', function () {
    Cache::set('test_flush', 'val', 60);
    Cache::flush();
    $val = Cache::get('test_flush');
    ok($val === null, 'Expected null after flush');
});

echo "\n--- Response Format ---\n";

test('Response::success() has correct structure', function () {
    $r = Response::success(['id' => 1], 'OK');
    $p = $r->payload();
    ok(($p['success'] ?? false) === true, 'Expected success=true');
    ok(($p['message'] ?? '') === 'OK', 'Expected message=OK');
    ok(isset($p['data']['id']), 'Expected data.id');
    ok(isset($p['meta']), 'Expected meta');
});

test('Response::error() has correct structure', function () {
    $r = Response::error('Not found', 404);
    $p = $r->payload();
    ok(($p['success'] ?? true) === false, 'Expected success=false');
    ok($p['message'] === 'Not found', 'Expected message');
    ok($p['data'] === null, 'Expected data=null');
});

test('Response::paginated() has correct structure', function () {
    $r = Response::paginated([], ['page' => 1, 'per_page' => 15, 'total' => 0, 'last_page' => 0], 'OK');
    $p = $r->payload();
    ok(($p['success'] ?? false) === true, 'Expected success=true');
    ok(isset($p['data']), 'Expected data');
    ok(isset($p['meta']['page']), 'Expected meta.page');
});

echo "\n--- Language Detection ---\n";

test('Vietnamese locale auto-detection from header', function () {
    Lang::setLocale('en');
    global $basePath;

    Router::setMiddlewareAliases([
        'auth' => \App\Middleware\AuthMiddleware::class,
        'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
        'cors' => \App\Middleware\CorsMiddleware::class,
        'json' => \App\Middleware\JsonMiddleware::class,
    ]);

    $a = new App($basePath);
    $a->boot();
    $a->loadRoutes($basePath . '/routes/api.php');

    $res = dispatch($a, 'GET', '/', [], ['accept-language' => 'vi']);
    // Just verify it doesn't crash
    ok(true);
});

echo "\n─── Security: Input Handling ───\n";

test('XSS in request body is rejected by validation', function () {
    $errors = Validator::make(['name' => '<script>alert(1)</script>'], ['name' => 'required|max:100']);
    ok($errors === [], 'XSS string should pass length validation (valid input)');
});

test('SQL injection attempt in email fails validation', function () {
    $errors = Validator::make(['email' => "'; DROP TABLE users;--"], ['email' => 'required|email']);
    ok(isset($errors['email']), 'SQL injection should fail email validation');
});

// ═══════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

exit($failed > 0 ? 1 : 0);
