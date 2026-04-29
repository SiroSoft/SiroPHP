<?php

declare(strict_types=1);

/**
 * API route definitions.
 *
 * Defines all application routes using the Router instance.
 * Routes are organized into groups with CORS middleware.
 *
 * @package App
 */

use App\Controllers\UserController;
use App\Controllers\AuthController;
use App\Middleware\CorsMiddleware;
use App\Middleware\JsonMiddleware;

$app->router->get('/', function (): array {
    return [
        'success' => true,
        'message' => 'Siro API Framework is running',
        'data' => [
            'name' => 'Siro API Framework',
            'version' => '0.8.1',
            'php' => PHP_VERSION,
        ],
        'meta' => [],
    ];
});

$app->router->group('/api', [CorsMiddleware::class], function ($router): void {
    // Public auth routes
    $router->post('/auth/register', [AuthController::class, 'register'])
        ->middleware([JsonMiddleware::class, 'throttle:30,1']);

    $router->post('/auth/login', [AuthController::class, 'login'])
        ->middleware([JsonMiddleware::class, 'throttle:60,1']);

    $router->post('/auth/refresh', [AuthController::class, 'refresh'])
        ->middleware([JsonMiddleware::class, 'throttle:30,1']);

    $router->post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware([JsonMiddleware::class, 'throttle:10,1']);

    $router->post('/auth/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware([JsonMiddleware::class, 'throttle:10,1']);

    $router->post('/auth/verify-email', [AuthController::class, 'verifyEmail'])
        ->middleware([JsonMiddleware::class, 'throttle:10,1']);

    // Protected auth routes
    $router->get('/auth/me', [AuthController::class, 'me'])
        ->middleware(['auth', 'throttle:120,1']);

    $router->post('/auth/logout', [AuthController::class, 'logout'])
        ->middleware(['auth', 'throttle:60,1']);

    // CRUD routes
    $router->get('/users', [UserController::class, 'index'])->cache(60);
    $router->get('/users/{id}', [UserController::class, 'show'])->cache(60);

    $router->post('/users', [UserController::class, 'store'])
        ->middleware([JsonMiddleware::class, 'auth', 'throttle:60,1']);

    $router->put('/users/{id}', [UserController::class, 'update'])
        ->middleware([JsonMiddleware::class, 'auth', 'throttle:60,1']);

    $router->delete('/users/{id}', [UserController::class, 'delete'])
        ->middleware(['auth', 'throttle:60,1']);
});