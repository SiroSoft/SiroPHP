<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Siro\Core\App;
use Siro\Core\Database;
use Siro\Core\Lang;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\Schema;
use Siro\Core\ValidationException;

abstract class TestCase extends BaseTestCase
{
    protected string $basePath;
    private static bool $tablesCreated = false;
    private static string $dbDriver = '';

    protected static function isSqlite(): bool
    {
        try {
            $pdo = Database::connection();
            return $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite';
        } catch (\Throwable) {
            return false;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->basePath = dirname(__DIR__);
        Lang::setLocale('en');

        $rateDir = $this->basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'rate_limit';
        if (is_dir($rateDir)) {
            foreach (glob($rateDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $f) {
                @unlink($f);
            }
        }

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
            $pdo = Database::connection();
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
            $pdo = Database::connection();
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

        $migrationsDir = dirname(__DIR__) . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            return;
        }

        // Detect driver — skip SQLite file deletion for MySQL/MariaDB
        try {
            $pdo = Database::connection();
            self::$dbDriver = (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable) {
            return;
        }

        // Build driver-specific SQL helpers
        $ai = 'INT AUTO_INCREMENT';        // auto_increment syntax
        $dt = 'DATETIME';                  // datetime type
        $ti = 'TINYINT';                   // tinyint type
        $ts = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'; // created_at default
        $insertMig = 'INSERT IGNORE INTO migrations (migration, batch) VALUES (:m, :b)';
        $quote = '`';                      // identifier quote char

        // Driver-specific SQL syntax
        if (in_array(self::$dbDriver, ['pgsql', 'postgres', 'postgresql'], true)) {
            $ai = 'SERIAL'; $dt = 'TIMESTAMP'; $ti = 'SMALLINT';
            $ts = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $insertMig = 'INSERT INTO migrations (migration, batch) VALUES (:m, :b) ON CONFLICT (migration) DO NOTHING';
            $quote = '"';
        } elseif (in_array(self::$dbDriver, ['mysql', 'mariadb'], true)) {
            $ai = 'INT AUTO_INCREMENT'; $dt = 'DATETIME'; $ti = 'TINYINT';
            $ts = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP';
            $insertMig = 'INSERT IGNORE INTO migrations (migration, batch) VALUES (:m, :b)';
            $quote = '`';
        } else {
            // SQLite
            $ai = 'INTEGER PRIMARY KEY AUTOINCREMENT'; $dt = 'TEXT'; $ti = 'INTEGER';
            $ts = 'TEXT DEFAULT (datetime(\'now\'))';
            $insertMig = 'INSERT OR IGNORE INTO migrations (migration, batch) VALUES (:m, :b)';
            $quote = '"';
        }

        $q = $quote; // shorthand

        // Create migrations table
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id $ai,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INT NOT NULL DEFAULT 1,
            created_at $ts
        )");

        // Create users table
        $idCol = self::$dbDriver === 'sqlite' ? 'id INTEGER PRIMARY KEY AUTOINCREMENT' : "id $ai PRIMARY KEY";
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            $idCol,
            name VARCHAR(255) NOT NULL DEFAULT '',
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL DEFAULT '',
            status $ti DEFAULT 1,
            token_version INT DEFAULT 1,
            role VARCHAR(50) DEFAULT 'user',
            verification_token VARCHAR(255) NULL,
            email_verified_at $dt NULL,
            password_reset_token VARCHAR(255) NULL,
            password_reset_expires_at $dt NULL,
            login_attempts INT DEFAULT 0,
            locked_until $dt NULL,
            created_at $dt,
            updated_at $dt,
            deleted_at $dt NULL
        )");

        // Create refresh_tokens table
        $pdo->exec("CREATE TABLE IF NOT EXISTS refresh_tokens (
            $idCol,
            user_id INT NOT NULL,
            jti VARCHAR(255),
            revoked SMALLINT DEFAULT 0,
            expires_at $dt,
            created_at $dt DEFAULT CURRENT_TIMESTAMP
        )");

        // Create jobs table
        $pdo->exec("CREATE TABLE IF NOT EXISTS jobs (
            $idCol,
            queue VARCHAR(255) NOT NULL DEFAULT 'default',
            payload TEXT,
            attempts INT DEFAULT 0,
            reserved_at INT NULL,
            available_at INT NOT NULL,
            created_at INT NOT NULL
        )");

        // Record migration so system doesn't try to run them
        $existing = $pdo->query("SELECT migration FROM {$q}migrations{$q}")->fetchAll(\PDO::FETCH_COLUMN);
        $existingMigrations = array_flip($existing ?: []);

        $files = glob($migrationsDir . '/*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            $name = basename($file);
            if (!isset($existingMigrations[$name])) {
                $batch = 1;
                try {
                    $migration = require $file;
                    if (is_object($migration) && method_exists($migration, 'up')) {
                        try { $migration->up(); } catch (\Throwable) {}
                    }
                    $pdo->prepare($insertMig)->execute(['m' => $name, 'b' => $batch]);
                } catch (\Throwable) {}
            }
        }

        // Start transaction for test isolation
        try {
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
            'throttle' => \Siro\Core\Middleware\ThrottleMiddleware::class,
            'cors' => \Siro\Core\Middleware\CorsMiddleware::class,
            'json' => \Siro\Core\Middleware\JsonMiddleware::class,
        ]);

        $app = new App($this->basePath);
        $app->boot();
        $_ENV['THROTTLE_FALLBACK'] = 'disabled';
        putenv('THROTTLE_FALLBACK=disabled');
        $app->loadRoutes($this->basePath . '/routes/api.php');

        self::ensureTablesCreated();

        return $app;
    }

    protected function dispatch(App $app, string $method, string $path, array $body = [], array $headers = []): Response
    {
        $queryParams = [];
        $pathParts = explode('?', $path, 2);
        $cleanPath = $pathParts[0];
        if (isset($pathParts[1])) {
            parse_str($pathParts[1], $queryParams);
        }
        $request = new Request($method, $cleanPath, $queryParams, $headers, $body, '127.0.0.1');
        try {
            return $app->router->dispatch($request);
        } catch (ValidationException $e) {
            return $e->toResponse();
        }
    }

    protected function responseJson(Response $response): array
    {
        ob_start();
        $response->send();
        $output = strval(ob_get_clean());
        $decoded = json_decode($output, true);
        return $decoded;
    }

    protected function authenticate(?App $app = null): array
    {
        $app ??= $this->createApp();
        $email = 'auth-' . uniqid() . '@test.com';

        $register = $this->dispatch($app, 'POST', '/api/auth/register', [
            'name' => 'Test Auth User',
            'email' => $email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertContains($register->statusCode(), [200, 201], 'Failed to register auth test user.');

        $login = $this->dispatch($app, 'POST', '/api/auth/login', [
            'email' => $email,
            'password' => 'secret123',
        ]);

        $this->assertSame(200, $login->statusCode(), 'Failed to login auth test user.');
        $loginJson = $this->responseJson($login);
        $loginData = $loginJson['data'] ?? [];
        $token = $loginData['token'] ?? '';
        $this->assertNotSame('', $token, 'Auth token missing from login response.');

        return [
            'authorization' => 'Bearer ' . $token,
            'content-type' => 'application/json',
        ];
    }

    protected function get(string $path, array $headers = []): TestResponse
    {
        $app = $this->createApp();
        return new TestResponse($this->dispatch($app, 'GET', $path, [], $headers));
    }

    protected function post(string $path, array $body = [], array $headers = []): TestResponse
    {
        $app = $this->createApp();
        return new TestResponse($this->dispatch($app, 'POST', $path, $body, $headers));
    }

    protected function put(string $path, array $body = [], array $headers = []): TestResponse
    {
        $app = $this->createApp();
        return new TestResponse($this->dispatch($app, 'PUT', $path, $body, $headers));
    }

    protected function delete(string $path, array $headers = []): TestResponse
    {
        $app = $this->createApp();
        return new TestResponse($this->dispatch($app, 'DELETE', $path, [], $headers));
    }

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
            sprintf('Failed asserting that table [%s] has row matching %s.', $table, json_encode($conditions, JSON_UNESCAPED_UNICODE))
        );
    }

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

        $this->assertEquals(0, $count, sprintf('Failed asserting that table [%s] does not have row matching %s.', $table, json_encode($conditions, JSON_UNESCAPED_UNICODE)));
    }
}

final class TestResponse
{
    private Response $response;
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

    public function assertOk(): self { return $this->assertStatus(200); }
    public function assertCreated(): self { return $this->assertStatus(201); }
    public function assertNoContent(): self { return $this->assertStatus(204); }
    public function assertUnauthorized(): self { return $this->assertStatus(401); }
    public function assertForbidden(): self { return $this->assertStatus(403); }
    public function assertNotFound(): self { return $this->assertStatus(404); }
    public function assertValidationError(): self { return $this->assertStatus(422); }
    public function assertServerError(): self { return $this->assertStatus(500); }

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

    public function json(): array
    {
        if ($this->parsedBody === null) {
            ob_start();
            $this->response->send();
            $output = ob_get_clean();
            $decoded = json_decode(strval($output), true);
            $this->parsedBody = is_array($decoded) ? $decoded : [];
        }
        return $this->parsedBody;
    }

    public function status(): int
    {
        return $this->response->statusCode();
    }
}
