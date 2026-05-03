<?php

declare(strict_types=1);

/**
 * HTTP entry point.
 *
 * Boots the application, loads routes, and dispatches
 * the incoming request. All requests should be rewritten
 * to this file (e.g., via .htaccess or nginx config).
 *
 * @package App
 */

use Siro\Core\App;
use Siro\Core\Router;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

try {
    $app = new App(BASE_PATH);

    Router::setMiddlewareAliases([
        'auth' => \App\Middleware\AuthMiddleware::class,
        'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
        'cors' => \App\Middleware\CorsMiddleware::class,
        'json' => \App\Middleware\JsonMiddleware::class,
    ]);

    $app->boot();
    $app->loadRoutes(BASE_PATH . '/routes/api.php');
    $app->run();
} catch (Throwable $e) {
    // Bootstrap failure - return JSON error response
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    
    $error = [
        'success' => false,
        'message' => 'Application bootstrap failed',
        'error' => $e->getMessage(),
    ];
    
    // Only show details in debug mode
    if (class_exists('\Siro\Core\Env')) {
        $debug = \Siro\Core\Env::bool('APP_DEBUG', false);
        if ($debug) {
            $error['trace'] = $e->getTraceAsString();
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
        }
    }
    
    echo json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit(1);
}
