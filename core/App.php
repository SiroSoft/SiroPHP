<?php

declare(strict_types=1);

namespace Siro\Core;

use Throwable;

final class App
{
    private readonly string $basePath;
    public readonly Router $router;
    private bool $debug;
    private bool $showDebugTrace;
    private float $startedAt;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->router = new Router();
        $this->debug = false;
        $this->showDebugTrace = false;
        $this->startedAt = microtime(true);
    }

    public function boot(): void
    {
        Env::load($this->basePath . DIRECTORY_SEPARATOR . '.env');

        $debug = Env::bool('APP_DEBUG', false);
        $appEnv = strtolower((string) Env::get('APP_ENV', 'production'));
        $this->debug = $debug;
        $this->showDebugTrace = $debug && $appEnv !== 'production';

        if ($this->showDebugTrace) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        }

        /** @var array<string, mixed> $dbConfig */
        $dbConfig = require $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        Database::configure($dbConfig);
        Cache::boot($this->basePath);
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function loadRoutes(string $routesFile): void
    {
        $app = $this;
        $router = $this->router;
        require $routesFile;
    }

    public function run(): void
    {
        Response::enableDebug($this->debug);
        Cache::resetRequestState();

        try {
            $request = Request::fromGlobals();
            $response = $this->router->dispatch($request);
            $this->setDebugMeta();
            $response->send();
        } catch (Throwable $e) {
            $this->logException($e);

            $errors = [];
            if ($this->showDebugTrace) {
                $errors = [
                    'type' => $e::class,
                    'trace' => $e->getTraceAsString(),
                ];
            }

            $this->setDebugMeta();
            Response::error('Internal Server Error', 500, $errors)->send();
        }
    }

    private function setDebugMeta(): void
    {
        if (!$this->debug) {
            return;
        }

        $executionTimeMs = (microtime(true) - $this->startedAt) * 1000;
        $memoryUsageMb = memory_get_peak_usage(true) / 1024 / 1024;

        Response::setDebugMeta([
            'execution_time_ms' => round($executionTimeMs, 2),
            'memory_usage_mb' => round($memoryUsageMb, 2),
            'cache' => Cache::requestStatus(),
        ]);
    }

    private function logException(Throwable $e): void
    {
        $logDir = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $logLine = sprintf(
            "[%s] %s: %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        file_put_contents($logDir . DIRECTORY_SEPARATOR . 'app.log', $logLine, FILE_APPEND);
    }
}
