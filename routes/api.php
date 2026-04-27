<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonMiddleware;
use Siro\Core\Response;

$app->router->get('/', function (): array {
    return [
        'success' => true,
        'message' => 'Siro API Framework v0.5 is running',
        'data' => [
            'name' => 'Siro API Framework',
            'version' => '0.5.0',
            'php' => PHP_VERSION,
        ],
        'meta' => [],
    ];
});

$app->router->group('/api', [CorsMiddleware::class], function ($router): void {
    $router->get('/users', [UserController::class, 'index'])->cache(60);
    $router->get('/users/{id}', [UserController::class, 'show'])->cache(60);

    $router->post('/users', [UserController::class, 'store'])
        ->middleware([JsonMiddleware::class]);

    $router->put('/users/{id}', [UserController::class, 'update'])
        ->middleware([JsonMiddleware::class]);

    $router->delete('/users/{id}', [UserController::class, 'delete']);

    $router->options('/users', fn (): Response => Response::noContent());
    $router->options('/users/{id}', fn (): Response => Response::noContent());
});

$app->router->group('', [CorsMiddleware::class], function ($router): void {
    $router->get('/users', [UserController::class, 'index'])->cache(60);
    $router->get('/users/{id}', [UserController::class, 'show'])->cache(60);
    $router->post('/users', [UserController::class, 'store'])->middleware([JsonMiddleware::class]);
    $router->put('/users/{id}', [UserController::class, 'update'])->middleware([JsonMiddleware::class]);
    $router->delete('/users/{id}', [UserController::class, 'delete']);
    $router->options('/users', fn (): Response => Response::noContent());
    $router->options('/users/{id}', fn (): Response => Response::noContent());
});
