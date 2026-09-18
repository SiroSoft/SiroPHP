<?php

declare(strict_types=1);

use Siro\Core\App;
use Siro\Core\Router;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

// Handle API preflight before route matching so OPTIONS requests receive the
// same CORS policy as the endpoint they are preparing to call.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    $allowedOrigins = '';
    $envFile = BASE_PATH . '/.env';
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '' && !str_starts_with($line, '#') && str_starts_with($line, 'CORS_ALLOWED_ORIGINS=')) {
                $allowedOrigins = trim(substr($line, strlen('CORS_ALLOWED_ORIGINS=')));
                break;
            }
        }
    }

    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $origins = array_filter(array_map('trim', explode(',', $allowedOrigins)));
    if ($allowedOrigins === '*' || in_array($origin, $origins, true)) {
        header('Access-Control-Allow-Origin: ' . ($allowedOrigins === '*' ? '*' : $origin));
        header('Access-Control-Allow-Credentials: true');
    }
    header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS,PATCH');
    $defaultHeaders = 'Content-Type,Authorization,X-Requested-With,X-CSRF-TOKEN,X-Request-Id,X-Siro-FE,X-Locale,Cache-Control,Accept,Origin';
    $requestedHeaders = (string) ($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? '');
    $allowHeaders = $defaultHeaders;
    if ($requestedHeaders !== '') {
        $merged = array_unique(array_filter(array_map('trim', array_merge(explode(',', $defaultHeaders), explode(',', $requestedHeaders)))));
        $allowHeaders = implode(',', $merged);
    }
    header('Access-Control-Allow-Headers: ' . $allowHeaders);
    header('Access-Control-Max-Age: 86400');
    header('Vary: Origin');
    http_response_code(204);
    exit(0);
}

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
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
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

    // Register event listeners
    \App\Listeners\SendWelcomeEmailListener::register();

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
