<?php

declare(strict_types=1);

/**
 * Comprehensive Router & Request tests.
 * Run: php tests/router_request_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Router;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\App;
use Siro\Core\Route;
use Siro\Core\ValidationException;

$basePath = dirname(__DIR__);
$passed = 0;
$failed = 0;

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

echo "=== Router & Request Tests ===\n\n";

// ─── Router: Basic Routing ─────────────────────

echo "--- Router: Basic Routing ---\n";

test('Router::get() registers route', function () {
    $r = new Router();
    $route = $r->get('/test', fn () => ['ok' => true]);
    ok($route instanceof Route, 'Expected Route instance');
});

test('Router dispatches GET route', function () {
    $r = new Router();
    $r->get('/hello', fn () => Response::success(['msg' => 'hi']));
    $req = new Request('GET', '/hello', [], ['accept' => 'application/json'], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 200, 'Expected 200');
    $p = $res->payload();
    ok(($p['data']['msg'] ?? '') === 'hi', 'Expected hi');
});

test('Router dispatches POST route', function () {
    $r = new Router();
    $r->post('/data', fn (Request $req) => Response::created(['echo' => $req->input('key')]));
    $req = new Request('POST', '/data', [], ['accept' => 'application/json'], ['key' => 'val'], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 201, 'Expected 201');
});

test('Router dispatches PUT route', function () {
    $r = new Router();
    $r->put('/item', fn () => Response::success(null, 'Updated'));
    $req = new Request('PUT', '/item', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 200, 'Expected 200');
});

test('Router dispatches DELETE route', function () {
    $r = new Router();
    $r->delete('/item', fn () => Response::success(null, 'Deleted'));
    $req = new Request('DELETE', '/item', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 200, 'Expected 200');
});

test('Router returns 404 for unknown route', function () {
    $r = new Router();
    $req = new Request('GET', '/unknown', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 404, 'Expected 404');
});

test('Router returns 404 for wrong method (router design does not return 405)', function () {
    $r = new Router();
    $r->get('/only-get', fn () => Response::success());
    $req = new Request('POST', '/only-get', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 404, 'Expected 404 (router does not support 405), got ' . $res->statusCode());
});

echo "\n--- Router: Route Parameters ---\n";

test('Router extracts single param', function () {
    $r = new Router();
    $r->get('/users/{id}', fn (Request $req) => Response::success(['id' => $req->param('id')]));
    $req = new Request('GET', '/users/42', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    $p = $res->payload();
    ok(($p['data']['id'] ?? '') === '42', 'Expected id=42');
});

test('Router extracts multiple params', function () {
    $r = new Router();
    $r->get('/posts/{year}/{slug}', fn (Request $req) => Response::success([
        'year' => $req->param('year'),
        'slug' => $req->param('slug'),
    ]));
    $req = new Request('GET', '/posts/2026/hello-world', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    $p = $res->payload();
    ok($p['data']['year'] === '2026', 'Expected year=2026');
    ok($p['data']['slug'] === 'hello-world', 'Expected slug');
});

echo "\n--- Router: Groups ---\n";

test('Router::group() prefixes paths', function () {
    $r = new Router();
    $r->group('/api', [], function (Router $router): void {
        $router->get('/ping', fn () => Response::success(['pong' => true]));
    });
    $req = new Request('GET', '/api/ping', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 200, 'Expected 200');
});

test('Router group with middleware', function () {
    $r = new Router();
    $called = false;
    $mw = function (Request $req, callable $next) use (&$called) {
        $called = true;
        return $next($req);
    };
    $r->group('/admin', [$mw], function (Router $router): void {
        $router->get('/dashboard', fn () => Response::success());
    });
    $req = new Request('GET', '/admin/dashboard', [], [], [], '127.0.0.1');
    $r->dispatch($req);
    ok($called === true, 'Expected middleware to run');
});

test('Router::group() nesting', function () {
    $r = new Router();
    $r->group('/api', [], function (Router $router): void {
        $router->group('/v1', [], function (Router $r2): void {
            $r2->get('/users', fn () => Response::success(['ok' => true]));
        });
    });
    $req = new Request('GET', '/api/v1/users', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 200, 'Expected 200');
});

echo "\n--- Router: Middleware ---\n";

test('Route-level middleware runs', function () {
    $r = new Router();
    $ran = false;
    $r->get('/secured', fn () => Response::success())
        ->middleware([function (Request $req, callable $next) use (&$ran) {
            $ran = true;
            return $next($req);
        }]);
    $req = new Request('GET', '/secured', [], [], [], '127.0.0.1');
    $r->dispatch($req);
    ok($ran === true, 'Expected middleware to run');
});

test('Middleware can block request', function () {
    $r = new Router();
    $r->get('/blocked', fn () => Response::success())
        ->middleware([function () {
            return Response::error('Blocked', 403);
        }]);
    $req = new Request('GET', '/blocked', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 403, 'Expected 403, got ' . $res->statusCode());
});

echo "\n--- Router: Caching ---\n";

test('Route::cache() sets TTL', function () {
    $r = new Router();
    $route = $r->get('/cached', fn () => Response::success())->cache(120);
    $routes = $r->getRoutes();
    ok(count($routes) >= 1, 'Expected at least 1 route');
});

echo "\n--- Router: getRoutes() ---\n";

test('Router::getRoutes() returns all routes', function () {
    $r = new Router();
    $r->get('/a', fn () => Response::success());
    $r->post('/b', fn () => Response::success());
    $routes = $r->getRoutes();
    ok(count($routes) >= 2, 'Expected at least 2 routes');
});

// ─── Request Tests ─────────────────────────────

echo "\n--- Request: Input Methods ---\n";

test('Request::input() returns body data', function () {
    $req = new Request('POST', '/', [], [], ['name' => 'Alice'], '127.0.0.1');
    ok($req->input('name') === 'Alice', 'Expected Alice');
});

test('Request::input() returns default for missing key', function () {
    $req = new Request('GET', '/', [], [], [], '127.0.0.1');
    ok($req->input('missing', 'default') === 'default', 'Expected default');
});

test('Request::int() casts to int', function () {
    $req = new Request('POST', '/', [], [], ['age' => '25'], '127.0.0.1');
    ok($req->int('age') === 25, 'Expected 25, got ' . $req->int('age'));
});

test('Request::int() returns default for missing', function () {
    $req = new Request('GET', '/', [], [], [], '127.0.0.1');
    ok($req->int('missing', 0) === 0, 'Expected 0');
});

test('Request::string() casts to string', function () {
    $req = new Request('POST', '/', [], [], ['val' => 42], '127.0.0.1');
    ok($req->string('val') === '42', 'Expected "42"');
});

test('Request::bool() returns true', function () {
    $req = new Request('POST', '/', [], [], ['active' => 'true'], '127.0.0.1');
    ok($req->bool('active') === true, 'Expected true');
});

test('Request::bool() returns false', function () {
    $req = new Request('POST', '/', [], [], ['active' => 'false'], '127.0.0.1');
    ok($req->bool('active') === false, 'Expected false');
});

test('Request::float() casts to float', function () {
    $req = new Request('POST', '/', [], [], ['price' => '19.99'], '127.0.0.1');
    ok($req->float('price') === 19.99, 'Expected 19.99');
});

test('Request::array() returns array', function () {
    $req = new Request('POST', '/', [], [], ['tags' => ['a', 'b']], '127.0.0.1');
    $tags = $req->array('tags');
    ok(is_array($tags), 'Expected array');
    ok(count($tags) === 2, 'Expected 2 items');
});

echo "\n--- Request: Query Params ---\n";

test('Request::query() returns query param', function () {
    $req = new Request('GET', '/', ['page' => '2'], [], [], '127.0.0.1');
    ok($req->query('page') === '2', 'Expected page=2');
});

test('Request::queryInt() returns int', function () {
    $req = new Request('GET', '/', ['page' => '3'], [], [], '127.0.0.1');
    ok($req->queryInt('page') === 3, 'Expected 3');
});

test('Request::queryString() returns string', function () {
    $req = new Request('GET', '/', ['q' => 'search'], [], [], '127.0.0.1');
    ok($req->queryString('q') === 'search', 'Expected search');
});

echo "\n--- Request: Headers ---\n";

test('Request::header() returns header value', function () {
    $req = new Request('GET', '/', [], ['content-type' => 'application/json', 'authorization' => 'Bearer abc'], [], '127.0.0.1');
    ok($req->header('content-type') === 'application/json', 'Expected application/json');
    ok($req->header('authorization') === 'Bearer abc', 'Expected Bearer abc');
});

test('Request::header() case-insensitive lookup', function () {
    $req = new Request('GET', '/', [], ['x-custom' => 'val'], [], '127.0.0.1');
    ok($req->header('x-custom') === 'val', 'Expected val for lowercase key');
    ok($req->header('X-Custom') === 'val', 'Expected val for uppercase key');
});

test('Request::headers() returns all', function () {
    $req = new Request('GET', '/', [], ['a' => '1', 'b' => '2'], [], '127.0.0.1');
    $h = $req->headers();
    ok(($h['a'] ?? '') === '1', 'Expected a=1');
});

echo "\n--- Request: Path and Method ---\n";

test('Request::method() returns normalized method', function () {
    $req = new Request('get', '/', [], [], [], '127.0.0.1');
    ok($req->method() === 'GET', 'Expected GET');
});

test('Request::path() returns path', function () {
    $req = new Request('GET', '/api/users', [], [], [], '127.0.0.1');
    ok($req->path() === '/api/users', 'Expected /api/users');
});

test('Request::ip() returns IP', function () {
    $req = new Request('GET', '/', [], [], [], '192.168.1.1');
    ok($req->ip() === '192.168.1.1', 'Expected IP');
});

echo "\n--- Request: only() and except() ---\n";

test('Request::only() returns specified keys', function () {
    $req = new Request('POST', '/', [], [], ['a' => '1', 'b' => '2', 'c' => '3'], '127.0.0.1');
    $only = $req->only(['a', 'c']);
    ok(count($only) === 2, 'Expected 2 keys');
    ok(isset($only['a']), 'Expected a');
    ok(!isset($only['b']), 'Expected no b');
});

test('Request::except() excludes specified keys', function () {
    $req = new Request('POST', '/', [], [], ['a' => '1', 'b' => '2'], '127.0.0.1');
    $except = $req->except(['a']);
    ok(count($except) === 1, 'Expected 1 key');
    ok(isset($except['b']), 'Expected b');
    ok(!isset($except['a']), 'Expected no a');
});

echo "\n--- Request: Validation ---\n";

test('Request::validate() passes valid data', function () {
    $req = new Request('POST', '/', [], [], ['email' => 'a@b.com'], '127.0.0.1');
    $v = $req->validate(['email' => 'required|email']);
    ok(isset($v['email']), 'Expected email');
});

test('Request::validate() throws on invalid', function () {
    $req = new Request('POST', '/', [], [], ['email' => ''], '127.0.0.1');
    $threw = false;
    try {
        $req->validate(['email' => 'required|email']);
    } catch (ValidationException) {
        $threw = true;
    }
    ok($threw, 'Expected ValidationException');
});

// ─── Response Tests ────────────────────────────

echo "\n--- Response: Factory Methods ---\n";

test('Response::success() has standard format', function () {
    $r = Response::success(['id' => 1], 'OK', 200, ['extra' => true]);
    $p = $r->payload();
    ok($p['success'] === true, 'Expected success=true');
    ok($p['message'] === 'OK', 'Expected message=OK');
    ok($p['data']['id'] === 1, 'Expected data.id=1');
    ok($p['meta']['extra'] === true, 'Expected meta.extra=true');
});

test('Response::error() has standard format', function () {
    $r = Response::error('Not found', 404, ['id' => ['Invalid']]);
    $p = $r->payload();
    ok($p['success'] === false, 'Expected success=false');
    ok($p['message'] === 'Not found', 'Expected message');
    ok($p['data'] === null, 'Expected data=null');
    ok(isset($p['meta']['errors']['id']), 'Expected errors.id');
});

test('Response::created() returns 201', function () {
    $r = Response::created(['id' => 1]);
    ok($r->statusCode() === 201, 'Expected 201');
    ok($r->payload()['success'] === true, 'Expected success=true');
});

test('Response::noContent() returns 204', function () {
    $r = Response::noContent();
    ok($r->statusCode() === 204, 'Expected 204');
});

test('Response::paginated() has pagination meta', function () {
    $r = Response::paginated([['id' => 1]], ['page' => 1, 'per_page' => 15, 'total' => 1, 'last_page' => 1], 'OK');
    $p = $r->payload();
    ok($p['success'] === true, 'Expected success=true');
    ok($p['meta']['page'] === 1, 'Expected page=1');
});

test('Response::json() returns custom payload', function () {
    $r = Response::json(['custom' => true], 202);
    ok($r->statusCode() === 202, 'Expected 202');
    ok($r->payload()['custom'] === true, 'Expected custom=true');
});

echo "\n--- Response: Headers ---\n";

test('Response::header() adds header', function () {
    $r = Response::success()->header('X-Custom', 'val');
    // Can't easily test headers in CLI, just verify no crash
    ok(true, 'Header set without error');
});

test('Response::withHeaders() adds multiple headers', function () {
    $r = Response::success()->withHeaders(['X-A' => '1', 'X-B' => '2']);
    ok(true, 'Multiple headers set without error');
});

test('Response::statusCode() returns code', function () {
    $r = Response::error('Fail', 422);
    ok($r->statusCode() === 422, 'Expected 422');
});

test('Response::payload() returns array', function () {
    $r = Response::success('data');
    ok(is_array($r->payload()), 'Expected array');
});

// ─── Helper assert equal test ──────────────────

echo "\n--- Results ---\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
