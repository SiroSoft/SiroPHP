<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Tests\TestCase;
use Siro\Core\Router;
use Siro\Core\Request;
use Siro\Core\Response;

final class RouteConstraintsTest extends TestCase
{
    public function testRouteWithNumericParam(): void
    {
        $router = new Router();
        $router->get('/users/{id}', fn (Request $req) => Response::success(['id' => (int) $req->param('id')]));
        $req = new Request('GET', '/users/42');
        $res = $router->dispatch($req);
        $this->assertEquals(200, $res->statusCode());
        $this->assertSame(42, $res->payload()['data']['id']);
    }

    public function testRouteWithAlphaParam(): void
    {
        $router = new Router();
        $router->get('/users/{name}', fn (Request $req) => Response::success(['name' => $req->param('name')]));
        $req = new Request('GET', '/users/john');
        $res = $router->dispatch($req);
        $this->assertEquals(200, $res->statusCode());
        $this->assertSame('john', $res->payload()['data']['name']);
    }

    public function testRouteGroupPrefixWorks(): void
    {
        $router = new Router();
        $router->group('/api', function ($r) { $r->get('/v1/users', fn () => Response::success('ok')); });
        $req = new Request('GET', '/api/v1/users');
        $res = $router->dispatch($req);
        $this->assertEquals(200, $res->statusCode());
    }
}
