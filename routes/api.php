<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Controllers\AuthController;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonMiddleware;
use Siro\Core\Response;

$app->router->get('/', function (): array {
    return [
        'success' => true,
        'message' => 'Siro API Framework v0.6 is running',
        'data' => [
            'name' => 'Siro API Framework',
            'version' => '0.6.0',
            'php' => PHP_VERSION,
        ],
        'meta' => [],
    ];
});

$app->router->group('/api', [CorsMiddleware::class], function ($router): void {
    $router->post('/auth/register', [AuthController::class, 'register'])
        ->middleware([JsonMiddleware::class, 'throttle:30,1']);

    $router->post('/auth/login', [AuthController::class, 'login'])
        ->middleware([JsonMiddleware::class, 'throttle:60,1']);

    $router->get('/auth/me', [AuthController::class, 'me'])
        ->middleware(['auth', 'throttle:120,1']);

    $router->get('/users', [UserController::class, 'index'])->cache(60);
    $router->get('/users/{id}', [UserController::class, 'show'])->cache(60);

    $router->post('/users', [UserController::class, 'store'])
        ->middleware([JsonMiddleware::class, 'auth', 'throttle:60,1']);

    $router->put('/users/{id}', [UserController::class, 'update'])
        ->middleware([JsonMiddleware::class, 'auth', 'throttle:60,1']);

    $router->delete('/users/{id}', [UserController::class, 'delete'])
        ->middleware(['auth', 'throttle:60,1']);

    $router->options('/users', fn (): Response => Response::noContent());
    $router->options('/users/{id}', fn (): Response => Response::noContent());
    $router->options('/auth/register', fn (): Response => Response::noContent());
    $router->options('/auth/login', fn (): Response => Response::noContent());
    $router->options('/auth/me', fn (): Response => Response::noContent());
});

$app->router->group('', [CorsMiddleware::class], function ($router): void {
    $router->post('/auth/register', [AuthController::class, 'register'])
        ->middleware([JsonMiddleware::class, 'throttle:30,1']);
    $router->post('/auth/login', [AuthController::class, 'login'])
        ->middleware([JsonMiddleware::class, 'throttle:60,1']);
    $router->get('/auth/me', [AuthController::class, 'me'])
        ->middleware(['auth', 'throttle:120,1']);

    $router->get('/users', [UserController::class, 'index'])->cache(60);
    $router->get('/users/{id}', [UserController::class, 'show'])->cache(60);
    $router->post('/users', [UserController::class, 'store'])->middleware([JsonMiddleware::class, 'auth', 'throttle:60,1']);
    $router->put('/users/{id}', [UserController::class, 'update'])->middleware([JsonMiddleware::class, 'auth', 'throttle:60,1']);
    $router->delete('/users/{id}', [UserController::class, 'delete'])->middleware(['auth', 'throttle:60,1']);
    $router->options('/users', fn (): Response => Response::noContent());
    $router->options('/users/{id}', fn (): Response => Response::noContent());
    $router->options('/auth/register', fn (): Response => Response::noContent());
    $router->options('/auth/login', fn (): Response => Response::noContent());
    $router->options('/auth/me', fn (): Response => Response::noContent());
});
