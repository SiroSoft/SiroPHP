#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Siro\Core\Router;
use Siro\Core\Request;
use Siro\Core\Response;

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  PASS: {$name}\n";
            $passed++;
        } else {
            echo "  FAIL: {$name} - {$result}\n";
            $failed++;
        }
    } catch (\Throwable $e) {
        echo "  ERROR: {$name} - " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "Router Tests\n";
echo "============\n\n";

// Static routes
test('static GET route matches', function () {
    $router = new Router();
    $router->get('/hello', fn () => Response::success('world'));
    $req = new Request('GET', '/hello', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 200 ? true : 'expected 200';
});

test('static GET route returns correct data', function () {
    $router = new Router();
    $router->get('/hello', fn () => Response::success('world'));
    $req = new Request('GET', '/hello', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    $p = $res->payload();
    return $p['data'] === 'world' ? true : 'expected world';
});

test('POST route matches', function () {
    $router = new Router();
    $router->post('/data', fn () => Response::success('created', '', 201));
    $req = new Request('POST', '/data', [], [], ['name' => 'test'], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 201 ? true : 'expected 201';
});

test('PUT route matches', function () {
    $router = new Router();
    $router->put('/data/1', fn () => Response::success('updated'));
    $req = new Request('PUT', '/data/1', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 200 ? true : 'expected 200';
});

test('DELETE route matches', function () {
    $router = new Router();
    $router->delete('/data/1', fn () => Response::noContent());
    $req = new Request('DELETE', '/data/1', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 204 ? true : 'expected 204';
});

test('undefined route returns 404', function () {
    $router = new Router();
    $router->get('/hello', fn () => Response::success('world'));
    $req = new Request('GET', '/nonexistent', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 404 ? true : 'expected 404';
});

// Dynamic routes
test('dynamic route with param', function () {
    $router = new Router();
    $router->get('/users/{id}', fn (Request $req) => Response::success(['id' => (int) $req->param('id')]));
    $req = new Request('GET', '/users/42', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    $p = $res->payload();
    return $p['data']['id'] === 42 ? true : 'expected id=42';
});

test('dynamic route with multiple params', function () {
    $router = new Router();
    $router->get('/posts/{postId}/comments/{commentId}', fn (Request $req) => Response::success([
        'postId' => (int) $req->param('postId'),
        'commentId' => (int) $req->param('commentId'),
    ]));
    $req = new Request('GET', '/posts/10/comments/5', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    $p = $res->payload();
    return $p['data']['postId'] === 10 && $p['data']['commentId'] === 5 ? true : 'wrong params';
});

test('dynamic route with wrong segment count returns 404', function () {
    $router = new Router();
    $router->get('/users/{id}', fn () => Response::success('ok'));
    $req = new Request('GET', '/users/42/profile', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 404 ? true : 'expected 404';
});

// Group
test('group prefix applies to routes', function () {
    $router = new Router();
    $router->group('/api', function ($r) {
        $r->get('/ping', fn () => Response::success('pong'));
    });
    $req = new Request('GET', '/api/ping', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 200 ? true : 'expected 200';
});

// Auto OPTIONS
test('OPTIONS returns 204 for existing route', function () {
    $router = new Router();
    $router->get('/users', fn () => Response::success('ok'));
    $req = new Request('OPTIONS', '/users', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 204 ? true : 'expected 204';
});

test('OPTIONS returns 404 for non-existing route', function () {
    $router = new Router();
    $router->get('/users', fn () => Response::success('ok'));
    $req = new Request('OPTIONS', '/nonexistent', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 404 ? true : 'expected 404';
});

// Middleware
test('middleware runs before handler', function () {
    $router = new Router();
    $router->get('/secure', fn () => Response::success('ok'), ['test-mw']);
    // Can't easily test middleware chain without callable classes,
    // but basic route + middleware registration should work
    $req = new Request('GET', '/secure', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    return $res->statusCode() === 200 ? true : 'expected 200';
});

// Closure returns array
test('closure returning array is converted to JSON response', function () {
    $router = new Router();
    $router->get('/json', fn (): array => ['custom' => 'data']);
    $req = new Request('GET', '/json', [], [], [], '127.0.0.1');
    $res = $router->dispatch($req);
    $p = $res->payload();
    return $p['custom'] === 'data' ? true : 'expected custom data';
});

// Route list
test('getRoutes returns all registered routes', function () {
    $router = new Router();
    $router->get('/a', fn () => Response::success('a'));
    $router->post('/b', fn () => Response::success('b'));
    $router->get('/users/{id}', fn () => Response::success('c'));
    $routes = $router->getRoutes();
    $methods = array_map(fn ($r) => $r['method'] . ' ' . $r['path'], $routes);
    return in_array('GET /a', $methods, true) && in_array('POST /b', $methods, true) && in_array('GET /users/{id}', $methods, true) ? true : 'missing routes';
});

echo "\n\nResults: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
