<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Siro\Core\App;
use Siro\Core\Database;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\Schema;
use Siro\Core\ValidationException;

/**
 * Base test case for SiroPHP application tests.
 *
 * Provides HTTP test helpers (get/post/put/delete), response
 * assertion helpers (assertOk, assertStatus, assertJson),
 * and database assertions (assertDatabaseHas, assertDatabaseMissing).
 *
 * @package App\Tests
 */

abstract class TestCase extends BaseTestCase
{
    protected string $basePath;
    private static bool $tablesCreated = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__);

        // Clean rate limit files so tests don't interfere with each other
        $rateDir = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'rate_limit';
        if (is_dir($rateDir)) {
            foreach (glob($rateDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $f) {
                @unlink($f);
            }
        }

        // Rollback previous test's transaction, start fresh one
        self::resetTransaction();
    }

    protected function tearDown(): void
    {
        self::rollbackTransaction();
        parent::tearDown();
    }

    protected static function resetTransaction(): void
    {
        try {
            $pdo = \Siro\Core\Database::connection();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
                $pdo->beginTransaction();
            }
        } catch (\Throwable) {
        }
    }

    protected static function rollbackTransaction(): void
    {
        try {
            $pdo = \Siro\Core\Database::connection();
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (\Throwable) {
        }
    }

    protected static function ensureTablesCreated(): void
    {
        if (self::$tablesCreated) {
            return;
        }
        self::$tablesCreated = true;

        // Delete existing test database to start clean
        $dbPath = dirname(__DIR__) . '/storage/test.db';
        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }

        $migrationsDir = dirname(__DIR__) . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            return;
        }
        $files = glob($migrationsDir . '/*.php') ?: [];
        if ($files === []) {
            return;
        }
        sort($files);
        foreach ($files as $file) {
            $migration = require $file;
            if (is_object($migration) && method_exists($migration, 'up')) {
                try {
                    $migration->up();
                } catch (\Throwable) {
                }
            }
        }

        // Start transaction for test isolation
        try {
            $pdo = \Siro\Core\Database::connection();
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }
        } catch (\Throwable) {
        }
    }

    protected function createApp(): App
    {
        Router::setMiddlewareAliases([
            'auth' => \App\Middleware\AuthMiddleware::class,
            'throttle' => \App\Middleware\ThrottleMiddleware::class,
            'cors' => \App\Middleware\CorsMiddleware::class,
            'json' => \App\Middleware\JsonMiddleware::class,
        ]);

        $app = new App($this->basePath);
        $app->boot();
        $_ENV['THROTTLE_FALLBACK'] = 'disabled';
        putenv('THROTTLE_FALLBACK=disabled');
        $app->loadRoutes($this->basePath . '/routes/api.php');

        // Run migrations after boot so Database is configured
        self::ensureTablesCreated();

        return $app;
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    protected function dispatch(App $app, string $method, string $path, array $body = [], array $headers = []): Response
    {
        // Extract query string from path
        $queryParams = [];
        $pathParts = explode('?', $path, 2);
        $cleanPath = $pathParts[0];
        if (isset($pathParts[1])) {
            parse_str($pathParts[1], $queryParams);
        }
        /** @var array<string, string> $headers */
        $request = new Request($method, $cleanPath, $queryParams, $headers, $body, '127.0.0.1');
        try {
            return $app->router->dispatch($request);
        } catch (ValidationException $e) {
            return $e->toResponse();
        }
    }

    /** @param array<string, string> $headers */
    protected function get(string $path, array $headers = []): TestResponse
    {
        $app = $this->createApp();
        return new TestResponse($this->dispatch($app, 'GET', $path, [], $headers));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    protected function post(string $path, array $body = [], array $headers = []): TestResponse
    {
        $app = $this->createApp();
        return new TestResponse($this->dispatch($app, 'POST', $path, $body, $headers));
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    protected function put(string $path, array $body = [], array $headers = []): TestResponse
    {
        $app = $this->createApp();
        return new TestResponse($this->dispatch($app, 'PUT', $path, $body, $headers));
    }

    /** @param array<string, string> $headers */
    protected function delete(string $path, array $headers = []): TestResponse
    {
        $app = $this->createApp();
        return new TestResponse($this->dispatch($app, 'DELETE', $path, [], $headers));
    }

    /** @param array<string, mixed> $conditions */
    protected function assertDatabaseHas(string $table, array $conditions, ?string $connection = null): void
    {
        $driver = Database::connection($connection)->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $wheres = [];
        $bindings = [];
        foreach ($conditions as $col => $val) {
            $wheres[] = "$col = :$col";
            $bindings[":$col"] = $val;
        }
        $sql = 'SELECT COUNT(*) FROM ' . ($driver === 'pgsql' ? '"' . $table . '"' : '`' . $table . '`') . ' WHERE ' . implode(' AND ', $wheres);
        $stmt = Database::connection($connection)->prepare($sql);
        $stmt->execute($bindings);
        $count = (int) $stmt->fetchColumn();

        $this->assertGreaterThan(
            0,
            $count,
            sprintf(
                'Failed asserting that table [%s] has row matching %s.',
                $table,
                json_encode($conditions, JSON_UNESCAPED_UNICODE)
            )
        );
    }

    /** @param array<string, mixed> $conditions */
    protected function assertDatabaseMissing(string $table, array $conditions, ?string $connection = null): void
    {
        $driver = Database::connection($connection)->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $wheres = [];
        $bindings = [];
        foreach ($conditions as $col => $val) {
            $wheres[] = "$col = :$col";
            $bindings[":$col"] = $val;
        }
        $sql = 'SELECT COUNT(*) FROM ' . ($driver === 'pgsql' ? '"' . $table . '"' : '`' . $table . '`') . ' WHERE ' . implode(' AND ', $wheres);
        $stmt = Database::connection($connection)->prepare($sql);
        $stmt->execute($bindings);
        $count = (int) $stmt->fetchColumn();

        $this->assertEquals(
            0,
            $count,
            sprintf(
                'Failed asserting that table [%s] does not have row matching %s.',
                $table,
                json_encode($conditions, JSON_UNESCAPED_UNICODE)
            )
        );
    }
}

final class TestResponse
{
    private Response $response;
    /** @var array<string, mixed>|null */
    private ?array $parsedBody = null;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    public function assertStatus(int $status): self
    {
        \PHPUnit\Framework\Assert::assertEquals($status, $this->response->statusCode(), "Expected status {$status}, got {$this->response->statusCode()}.");
        return $this;
    }

    public function assertOk(): self
    {
        return $this->assertStatus(200);
    }

    public function assertCreated(): self
    {
        return $this->assertStatus(201);
    }

    public function assertNoContent(): self
    {
        return $this->assertStatus(204);
    }

    public function assertUnauthorized(): self
    {
        return $this->assertStatus(401);
    }

    public function assertForbidden(): self
    {
        return $this->assertStatus(403);
    }

    public function assertNotFound(): self
    {
        return $this->assertStatus(404);
    }

    public function assertValidationError(): self
    {
        return $this->assertStatus(422);
    }

    public function assertServerError(): self
    {
        return $this->assertStatus(500);
    }

    /** @param array<string, mixed> $expected */
    public function assertJson(array $expected): self
    {
        $body = $this->json();
        foreach ($expected as $key => $value) {
            \PHPUnit\Framework\Assert::assertArrayHasKey($key, $body, "Response missing key '{$key}'.");
            \PHPUnit\Framework\Assert::assertEquals($value, $body[$key], "Key '{$key}' mismatch.");
        }
        return $this;
    }

    public function assertJsonPath(string $path, mixed $expected): self
    {
        $keys = explode('.', $path);
        $value = $this->json();
        foreach ($keys as $key) {
            \PHPUnit\Framework\Assert::assertIsArray($value, "Path '{$path}' not found.");
            \PHPUnit\Framework\Assert::assertArrayHasKey($key, $value, "Key '{$key}' not found in path '{$path}'.");
            $value = $value[$key];
        }
        \PHPUnit\Framework\Assert::assertEquals($expected, $value, "Path '{$path}' mismatch.");
        return $this;
    }

    public function assertHeader(string $name, string $value): self
    {
        \PHPUnit\Framework\Assert::assertContains("{$name}: {$value}", $this->response->getHeaders());
        return $this;
    }

    /** @return array<string, mixed> */
    public function json(): array
    {
        if ($this->parsedBody === null) {
            ob_start();
            $this->response->send();
            $output = ob_get_clean();
            $this->parsedBody = json_decode((string) $output, true) ?? [];
        }
        return $this->parsedBody;
    }

    public function status(): int
    {
        return $this->response->statusCode();
    }
}
