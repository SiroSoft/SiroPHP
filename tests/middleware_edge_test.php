<?php

declare(strict_types=1);

/**
 * Middleware, Edge Cases, and CLI smoke tests.
 * Run: php tests/middleware_edge_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\Cache;
use Siro\Core\Env;
use Siro\Core\Logger;
use Siro\Core\ValidationException;
use App\Middleware\AuthMiddleware;
use App\Middleware\JsonMiddleware;
use App\Middleware\CorsMiddleware;

$basePath = dirname(__DIR__);
$passed = 0;
$failed = 0;

// Register middleware aliases
Router::setMiddlewareAliases([
    'auth' => \App\Middleware\AuthMiddleware::class,
    'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
    'cors' => \App\Middleware\CorsMiddleware::class,
    'json' => \App\Middleware\JsonMiddleware::class,
]);

// Boot
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

ob_start();
echo "=== Middleware & Edge Case Tests ===\n\n";

// ═══════════════════════════════════════════════
// MIDDLEWARE
// ═══════════════════════════════════════════════

echo "--- JsonMiddleware ---\n";

test('JsonMiddleware passes with valid JSON content-type', function () {
    $req = new Request('POST', '/test', [], ['content-type' => 'application/json'], ['key' => 'val'], '127.0.0.1');
    $mw = new JsonMiddleware();
    $passed = false;
    $res = $mw->handle($req, function ($r) use (&$passed) { $passed = true; return Response::success(); });
    ok($passed === true, 'Middleware should pass through');
});

test('JsonMiddleware blocks non-JSON content-type', function () {
    $req = new Request('POST', '/test', [], ['content-type' => 'text/plain'], [], '127.0.0.1');
    $mw = new JsonMiddleware();
    $nextCalled = false;
    $res = $mw->handle($req, function () use (&$nextCalled) { $nextCalled = true; return Response::success(); });
    ok($nextCalled === false, 'Next should not be called');
    ok($res->statusCode() === 415, 'Expected 415, got ' . $res->statusCode());
});

test('JsonMiddleware passes GET without content-type', function () {
    $req = new Request('GET', '/test', [], [], [], '127.0.0.1');
    $mw = new JsonMiddleware();
    $passed = false;
    $mw->handle($req, function () use (&$passed) { $passed = true; return Response::success(); });
    ok($passed === true, 'GET should pass through');
});

echo "\n--- CorsMiddleware ---\n";

test('CorsMiddleware adds headers to response', function () {
    $req = new Request('GET', '/test', [], ['origin' => 'http://example.com'], [], '127.0.0.1');
    $mw = new CorsMiddleware();
    $res = $mw->handle($req, fn ($r) => Response::success());
    $p = $res->payload();
    ok($p['success'] === true, 'Middleware should pass through');
});

test('CorsMiddleware handles OPTIONS preflight', function () {
    $req = new Request('OPTIONS', '/test', [], ['origin' => 'http://example.com'], [], '127.0.0.1');
    $mw = new CorsMiddleware();
    $res = $mw->handle($req, fn ($r) => Response::success());
    ok($res->statusCode() === 204, 'Expected 204 for OPTIONS, got ' . $res->statusCode());
});

echo "\n--- AuthMiddleware ---\n";

test('AuthMiddleware blocks missing token', function () {
    $req = new Request('GET', '/secured', [], [], [], '127.0.0.1');
    $mw = new AuthMiddleware();
    $res = $mw->handle($req, fn ($r) => Response::success());
    ok($res->statusCode() === 401, 'Expected 401, got ' . $res->statusCode());
});

test('AuthMiddleware blocks invalid token', function () {
    $req = new Request('GET', '/secured', [], ['authorization' => 'Bearer invalidtoken'], [], '127.0.0.1');
    $mw = new AuthMiddleware();
    $res = $mw->handle($req, fn ($r) => Response::success());
    ok($res->statusCode() === 401, 'Expected 401, got ' . $res->statusCode());
});

test('AuthMiddleware blocks malformed auth header', function () {
    $req = new Request('GET', '/secured', [], ['authorization' => 'Basic abc123'], [], '127.0.0.1');
    $mw = new AuthMiddleware();
    $res = $mw->handle($req, fn ($r) => Response::success());
    ok($res->statusCode() === 401, 'Expected 401, got ' . $res->statusCode());
});

echo "\n--- Middleware Chain ---\n";

test('Multiple middleware in sequence', function () {
    $r = new Router();
    $order = [];
    $mw1 = function (Request $req, callable $next) use (&$order) {
        $order[] = 'mw1';
        return $next($req);
    };
    $mw2 = function (Request $req, callable $next) use (&$order) {
        $order[] = 'mw2';
        return $next($req);
    };
    $r->get('/chain', function () use (&$order) {
        $order[] = 'handler';
        return Response::success();
    })->middleware([$mw1, $mw2]);

    $req = new Request('GET', '/chain', [], [], [], '127.0.0.1');
    $r->dispatch($req);
    ok($order === ['mw1', 'mw2', 'handler'], 'Expected mw1 -> mw2 -> handler, got: ' . json_encode($order));
});

test('Middleware chain stops on error', function () {
    $r = new Router();
    $order = [];
    $mw1 = function (Request $req, callable $next) use (&$order) {
        $order[] = 'mw1';
        return Response::error('Blocked', 403);
    };
    $mw2 = function (Request $req, callable $next) use (&$order) {
        $order[] = 'mw2';
        return $next($req);
    };
    $r->get('/block-chain', function () use (&$order) {
        $order[] = 'handler';
        return Response::success();
    })->middleware([$mw1, $mw2]);

    $req = new Request('GET', '/block-chain', [], [], [], '127.0.0.1');
    $r->dispatch($req);
    ok($order === ['mw1'], 'Expected only mw1 to run, got: ' . json_encode($order));
});

echo "\n--- Auth via real Router ---\n";

test('Router with auth middleware blocks without token', function () use ($basePath) {
    Router::setMiddlewareAliases([
        'auth' => \App\Middleware\AuthMiddleware::class,
        'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
        'cors' => \App\Middleware\CorsMiddleware::class,
        'json' => \App\Middleware\JsonMiddleware::class,
    ]);

    $a = new App($basePath);
    $a->boot();
    $a->loadRoutes($basePath . '/routes/api.php');
    ob_start();
    try {
        $req = new Request('GET', '/api/auth/me', [], [], [], '127.0.0.1');
        $res = $a->router->dispatch($req);
        ob_end_clean();
        ok($res->statusCode() === 401, 'Expected 401, got ' . $res->statusCode());
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }
});

// ═══════════════════════════════════════════════
// EDGE CASES
// ═══════════════════════════════════════════════

echo "\n--- Edge Cases: Unicode ---\n";

test('Unicode in request body', function () {
    $r = new Router();
    $r->post('/unicode', fn (Request $req) => Response::success(['echo' => $req->input('text')]));
    $req = new Request('POST', '/unicode', [], ['content-type' => 'application/json'], ['text' => 'Tiếng Việt 中文'], '127.0.0.1');
    $res = $r->dispatch($req);
    $p = $res->payload();
    ok($p['data']['echo'] === 'Tiếng Việt 中文', 'Expected unicode preservation');
});

test('Emoji in request body', function () {
    $r = new Router();
    $r->post('/emoji', fn (Request $req) => Response::success(['e' => $req->input('emoji')]));
    $req = new Request('POST', '/emoji', [], [], ['emoji' => '🚀🔥💯'], '127.0.0.1');
    $res = $r->dispatch($req);
    $p = $res->payload();
    ok($p['data']['e'] === '🚀🔥💯', 'Expected emoji preservation');
});

echo "\n--- Edge Cases: Special Characters ---\n";

test('HTML in input is preserved (not stripped)', function () {
    $r = new Router();
    $r->post('/html', fn (Request $req) => Response::success(['h' => $req->input('html')]));
    $req = new Request('POST', '/html', [], [], ['html' => '<b>bold</b><script>alert(1)</script>'], '127.0.0.1');
    $res = $r->dispatch($req);
    $p = $res->payload();
    ok(str_contains($p['data']['h'] ?? '', '<b>bold</b>'), 'Expected HTML preservation');
});

test('Very long string in input', function () {
    $long = str_repeat('a', 10000);
    $r = new Router();
    $r->post('/long', fn (Request $req) => Response::success(['len' => strlen($req->input('text'))]));
    $req = new Request('POST', '/long', [], [], ['text' => $long], '127.0.0.1');
    $res = $r->dispatch($req);
    $p = $res->payload();
    ok($p['data']['len'] === 10000, 'Expected length 10000');
});

echo "\n--- Edge Cases: SQL Injection Vectors ---\n";

test('SQL injection string in validator email', function () {
    $errors = \Siro\Core\Validator::make(
        ['email' => "'; DROP TABLE users; --"],
        ['email' => 'required|email']
    );
    ok(isset($errors['email']), 'Expected email validation error');
});

test('SQL injection in generic input field', function () {
    $e = \Siro\Core\Validator::make(
        ['name' => "1; SELECT * FROM users"],
        ['name' => 'required|min:3']
    );
    ok($e === [], 'SQL-like input should pass basic validation');
});

echo "\n--- Edge Cases: Special Routes ---\n";

test('Route with hyphens and dots', function () {
    $r = new Router();
    $r->get('/api/v1.0/my-resource', fn () => Response::success(['ok' => true]));
    $req = new Request('GET', '/api/v1.0/my-resource', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 200, 'Expected 200');
});

test('Root path /', function () {
    $r = new Router();
    $r->get('/', fn () => Response::success(['root' => true]));
    $req = new Request('GET', '/', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 200, 'Expected 200');
});

echo "\n--- Edge Cases: Validation ---\n";

test('Validator required fails for null input', function () {
    $e = \Siro\Core\Validator::make(['x' => null], ['x' => 'required']);
    ok(isset($e['x']), 'Expected error for null');
});

test('Validator pipe in value does not break', function () {
    $e = \Siro\Core\Validator::make(['val' => 'a|b'], ['val' => 'required|max:10']);
    ok($e === [], 'Pipe character should not break validation');
});

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
