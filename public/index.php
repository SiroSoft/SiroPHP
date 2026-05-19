<?php

declare(strict_types=1);

use Siro\Core\App;
use Siro\Core\Router;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

function siroJsonError(int $statusCode, string $message, ?Throwable $e = null): never
{
    static $recursionGuard = false;
    if ($recursionGuard) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo '{"success":false,"message":"Internal error"}';
        exit(1);
    }
    $recursionGuard = true;

    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }

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

    $json = json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    echo $json !== false ? $json : '{"success":false,"message":"Internal error"}';
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

if (extension_loaded('pcntl')) {
    pcntl_signal(SIGTERM, function (): void {
        \Siro\Core\App::shutdown();
        exit(0);
    });
}

try {
    $app = new App(BASE_PATH);

    Router::setMiddlewareAliases([
        'auth' => \App\Middleware\AuthMiddleware::class,
        'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
        'cors' => \Siro\Core\Middleware\CorsMiddleware::class,
        'json' => \Siro\Core\Middleware\JsonMiddleware::class,
    ]);

    $app->boot();

    // Apply log sanitization config from .env
    \Siro\Core\Logger::setSanitizeConfig([
        'headers' => array_map('trim', explode(',', (string) \Siro\Core\Env::get('LOG_SANITIZE_HEADERS', 'authorization,cookie,x-api-key,session-id'))),
        'body' => array_map('trim', explode(',', (string) \Siro\Core\Env::get('LOG_SANITIZE_BODY', 'password,token,otp,secret,credit_card,card_number,cvv,pin,ssn'))),
        'query' => array_map('trim', explode(',', (string) \Siro\Core\Env::get('LOG_SANITIZE_QUERY', 'token,key,secret,api_key,code'))),
    ]);

    $app->loadRoutes(BASE_PATH . '/routes/api.php');
    $app->run();
} catch (Throwable $e) {
    siroJsonError(500, 'Application bootstrap failed', $e);
}
