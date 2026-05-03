<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\ValidationException;

abstract class TestCase extends BaseTestCase
{
    protected string $basePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__);
    }

    protected function createApp(): App
    {
        Router::setMiddlewareAliases([
            'auth' => \App\Middleware\AuthMiddleware::class,
            'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
            'cors' => \App\Middleware\CorsMiddleware::class,
            'json' => \App\Middleware\JsonMiddleware::class,
        ]);

        $app = new App($this->basePath);
        $app->boot();
        $app->loadRoutes($this->basePath . '/routes/api.php');
        return $app;
    }

    protected function dispatch(App $app, string $method, string $path, array $body = [], array $headers = []): Response
    {
        $request = new Request($method, $path, [], $headers, $body, '127.0.0.1');
        try {
            return $app->router->dispatch($request);
        } catch (ValidationException $e) {
            return $e->toResponse();
        }
    }
}
