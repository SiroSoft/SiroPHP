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
        $router->get('/users/{id}', function (Request $req): Response {
            $rawId = $req->param('id');
            /** @var int|string $rawId */
            return Response::success(['id' => (int) $rawId]);
        });
        $req = new Request('GET', '/users/42');
        $res = $router->dispatch($req);
        /** @var Response $res */
        $this->assertEquals(200, $res->statusCode());
        $payload = $res->payload();
        $payloadData = $payload['data'] ?? [];
        /** @var array<string, mixed> $payloadData */
        $this->assertSame(42, $payloadData['id']);
    }

    public function testRouteWithAlphaParam(): void
    {
        $router = new Router();
        $router->get('/users/{name}', function (Request $req): Response {
            $name = $req->param('name');
            /** @var string $name */
            return Response::success(['name' => $name]);
        });
        $req = new Request('GET', '/users/john');
        $res = $router->dispatch($req);
        /** @var Response $res */
        $this->assertEquals(200, $res->statusCode());
        $payload = $res->payload();
        $payloadData = $payload['data'] ?? [];
        /** @var array<string, mixed> $payloadData */
        $this->assertSame('john', $payloadData['name']);
    }

    public function testRouteGroupPrefixWorks(): void
    {
        $router = new Router();
        $router->group('/api', function (\Siro\Core\Router $r): void {
            $r->get('/v1/users', fn () => Response::success(null, 'ok'));
        });
        $req = new Request('GET', '/api/v1/users');
        $res = $router->dispatch($req);
        /** @var Response $res */
        $this->assertEquals(200, $res->statusCode());
    }
}
