<?php

declare(strict_types=1);

use Siro\Core\App;
use Siro\Core\Router;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

function siroJsonError(int $statusCode, string $message, ?Throwable $e = null): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    $error = [
        'success' => false,
        'message' => $message,
    ];

    if ($e !== null) {
        $error['error'] = $e->getMessage();
        if (class_exists('\Siro\Core\Env') && \Siro\Core\Env::bool('APP_DEBUG', false)) {
            $error['trace'] = $e->getTraceAsString();
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
        }
    }

    echo json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit(1);
}

set_exception_handler(function (Throwable $e): void {
    siroJsonError(500, 'Unhandled exception', $e);
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        siroJsonError(500, 'Fatal error', new ErrorException(
            $error['message'], 0, $error['type'], $error['file'], $error['line']
        ));
    }
});

try {
    $app = new App(BASE_PATH);

    Router::setMiddlewareAliases([
        'auth' => \App\Middleware\AuthMiddleware::class,
        'throttle' => \App\Middleware\ThrottleMiddleware::class,
        'cors' => \App\Middleware\CorsMiddleware::class,
        'json' => \App\Middleware\JsonMiddleware::class,
    ]);

    $app->boot();
    $app->loadRoutes(BASE_PATH . '/routes/api.php');
    $app->run();
} catch (Throwable $e) {
    siroJsonError(500, 'Application bootstrap failed', $e);
}
