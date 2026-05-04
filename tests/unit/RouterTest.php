<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Tests\TestCase;
use Siro\Core\Router;
use Siro\Core\Request;
use Siro\Core\Response;

final class RouterTest extends TestCase
{
    public function testStaticGetRouteMatches(): void
    {
        $router = new Router();
        $router->get('/hello', fn () => Response::success('world'));
        $req = new Request('GET', '/hello');
        $res = $router->dispatch($req);
        $this->assertEquals(200, $res->statusCode());
    }

    public function testPostRouteMatches(): void
    {
        $router = new Router();
        $router->post('/data', fn () => Response::success('created', '', 201));
        $req = new Request('POST', '/data');
        $res = $router->dispatch($req);
        $this->assertEquals(201, $res->statusCode());
    }

    public function testPutRouteMatches(): void
    {
        $router = new Router();
        $router->put('/data/1', fn () => Response::success('updated'));
        $req = new Request('PUT', '/data/1');
        $res = $router->dispatch($req);
        $this->assertEquals(200, $res->statusCode());
    }

    public function testDeleteRouteMatches(): void
    {
        $router = new Router();
        $router->delete('/data/1', fn () => Response::noContent());
        $req = new Request('DELETE', '/data/1');
        $res = $router->dispatch($req);
        $this->assertEquals(204, $res->statusCode());
    }

    public function testUndefinedRouteReturns404(): void
    {
        $router = new Router();
        $router->get('/hello', fn () => Response::success('world'));
        $req = new Request('GET', '/nonexistent');
        $res = $router->dispatch($req);
        $this->assertEquals(404, $res->statusCode());
    }

    public function testDynamicRouteWithParam(): void
    {
        $router = new Router();
        $router->get('/users/{id}', fn (Request $req) => Response::success(['id' => (int) $req->param('id')]));
        $req = new Request('GET', '/users/42');
        $res = $router->dispatch($req);
        $this->assertSame(42, $res->payload()['data']['id']);
    }

    public function testDynamicRouteWithMultipleParams(): void
    {
        $router = new Router();
        $router->get('/posts/{postId}/comments/{commentId}', fn (Request $req) => Response::success([
            'postId' => (int) $req->param('postId'),
            'commentId' => (int) $req->param('commentId'),
        ]));
        $req = new Request('GET', '/posts/10/comments/5');
        $res = $router->dispatch($req);
        $data = $res->payload()['data'];
        $this->assertSame(10, $data['postId']);
        $this->assertSame(5, $data['commentId']);
    }

    public function testGroupPrefixAppliesToRoutes(): void
    {
        $router = new Router();
        $router->group('/api', function ($r) { $r->get('/ping', fn () => Response::success('pong')); });
        $req = new Request('GET', '/api/ping');
        $res = $router->dispatch($req);
        $this->assertEquals(200, $res->statusCode());
    }

    public function testOptionsReturns204ForExistingRoute(): void
    {
        $router = new Router();
        $router->get('/users', fn () => Response::success('ok'));
        $req = new Request('OPTIONS', '/users');
        $res = $router->dispatch($req);
        $this->assertEquals(204, $res->statusCode());
    }

    public function testClosureReturningArrayIsConvertedToJson(): void
    {
        $router = new Router();
        $router->get('/json', fn (): array => ['custom' => 'data']);
        $req = new Request('GET', '/json');
        $res = $router->dispatch($req);
        $this->assertSame('data', $res->payload()['custom']);
    }

    public function testGetRoutesReturnsAllRegisteredRoutes(): void
    {
        $router = new Router();
        $router->get('/a', fn () => Response::success('a'));
        $router->post('/b', fn () => Response::success('b'));
        $router->get('/users/{id}', fn () => Response::success('c'));
        $routes = $router->getRoutes();
        $methods = array_map(fn ($r) => $r['method'] . ' ' . $r['path'], $routes);
        $this->assertContains('GET /a', $methods);
        $this->assertContains('POST /b', $methods);
        $this->assertContains('GET /users/{id}', $methods);
    }
}
