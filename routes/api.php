<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use Siro\Core\Middleware\CorsMiddleware;
use Siro\Core\Middleware\JsonMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Siro\Core\Lang;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Storage;

use Siro\Core\Metrics;

/** @var \Siro\Core\App $app */

// Prometheus metrics endpoint (no auth, no version)
Metrics::init('siro', true);
Metrics::registerRoute($app->router);
$app->router->get('/health/live', function (): array {
    return [
        'success' => true,
        'message' => 'OK',
        'data' => [
            'status' => 'alive',
            'time' => date('c'),
        ],
    ];
})->middleware('throttle:30,1');

$app->router->get('/health/ready', function (): array {
    $dbOk = false;
    try {
        \Siro\Core\Database::connection()->query('SELECT 1');
        $dbOk = true;
    } catch (\Throwable) {
    }
    return [
        'success' => true,
        'message' => 'OK',
        'data' => [
            'status' => $dbOk ? 'ready' : 'degraded',
            'version' => \Siro\Core\Console::getVersion(),
            'php' => PHP_VERSION,
            'database' => $dbOk ? 'connected' : 'unreachable',
            'time' => date('c'),
        ],
    ];
});

// API Versioning registration
\Siro\Core\Middleware\VersionMiddleware::register(1, '/api/v1');
\Siro\Core\Middleware\VersionMiddleware::register(2, '/api/v2');

$app->router->get('/', function (Request $req): mixed {
    $accept = strval($req->header('accept', ''));

    $isBrowser = str_contains($accept, 'text/html') && !str_contains($accept, 'application/json');

    if ($isBrowser) {
        $file = __DIR__ . '/../public/index.html';
        if (file_exists($file)) {
            $html = file_get_contents($file);
            return Response::raw($html !== false ? $html : '', 'text/html; charset=utf-8');
        }
    }

    // Default: Return JSON API response
    return [
        'success' => true,
        'message' => Lang::get('messages.welcome'),
        'data' => [
            'name' => 'Siro API Framework',
            'version' => \Siro\Core\Console::getVersion(),
            'php' => PHP_VERSION,
            'locale' => Lang::locale(),
        ],
        'meta' => [],
    ];
});

$app->router->get('/health', function (): array {
    $dbOk = false;
    try {
        \Siro\Core\Database::connection()->query('SELECT 1');
        $dbOk = true;
    } catch (\Throwable) {
    }
    return [
        'success' => true,
        'message' => 'OK',
        'data' => [
            'status' => 'healthy',
            'database' => $dbOk ? 'connected' : 'unreachable',
            'time' => date('c'),
        ],
    ];
})->middleware('throttle:30,1');

$app->router->group('/api', [SecurityHeadersMiddleware::class, CorsMiddleware::class, 'version', 'etag', 'metrics'], function (\Siro\Core\Router $router): void {
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

    $router->resource('products', \App\Controllers\ProductController::class, ['auth', 'throttle:60,1']);
    $router->resource('categories', \App\Controllers\CategoryController::class, ['auth', 'throttle:60,1']);
    $router->resource('tags', \App\Controllers\TagController::class, ['auth', 'throttle:60,1']);
    $router->resource('orders', \App\Controllers\OrderController::class, ['auth', 'throttle:60,1']);
    $router->resource('posts', \App\Controllers\PostController::class, ['auth', 'throttle:60,1']);
    $router->resource('users', \App\Controllers\UserController::class, ['auth', 'throttle:60,1']);

    // Upload & Lang demo routes
    $router->post('/upload/avatar', function (Request $req): Response {
        $file = $req->file('avatar');
        if ($file === null || !$file->isValid()) {
            return Response::error('No file uploaded', 422);
        }
        $path = $file->store('avatars');
        return Response::success([
            'path' => $path,
            'url' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ], 'Avatar uploaded');
    })->middleware([JsonMiddleware::class, 'throttle:10,1']);

    $router->get('/profile', function (Request $req): array {
        $locale = $req->queryString('locale', 'en');
        Lang::setLocale($locale);

        $name = $req->query('name', 'Guest');
        $greeting = Lang::get('messages.greeting', ['name' => $name]);
        $messagesCount = Lang::has('validation') ? count((array) Lang::get('validation')) : 0;

        return [
            'success' => true,
            'message' => $greeting,
            'data' => [
                'name' => $name,
                'locale' => $locale,
                'greeting' => $greeting,
                'messages_count' => $messagesCount,
                'available_locales' => ['en', 'vi'],
            ],
        ];
    });
});
