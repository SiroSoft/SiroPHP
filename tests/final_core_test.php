<?php

declare(strict_types=1);

/**
 * Tests for remaining untested core files: Env, Resource, DB, ValidationException, Route.
 * Run: php tests/final_core_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Env;
use Siro\Core\Resource;
use Siro\Core\DB;
use Siro\Core\Database;
use Siro\Core\ValidationException;
use Siro\Core\Response;
use Siro\Core\Route;
use Siro\Core\Router;

$basePath = dirname(__DIR__);
$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  \033[32m✓\033[0m {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \033[31m✗ {$name}: {$e->getMessage()}\033[0m\n";
        $failed++;
    }
}

function ok(bool $condition, string $msg): void
{
    if (!$condition) {
        throw new RuntimeException($msg);
    }
}

ob_start();

// Boot for DB
$app = new \Siro\Core\App($basePath);
$app->boot();

echo "=== Final Core Files Tests ===\n\n";

// ─── Env ───────────────────────────────────────

echo "--- Env ---\n";

test('Env::get() returns existing key', function () {
    $val = Env::get('APP_ENV');
    ok($val !== null, 'Expected APP_ENV to exist');
});

test('Env::get() returns default for missing', function () {
    $val = Env::get('NONEXISTENT_KEY_XYZ', 'default_val');
    ok($val === 'default_val', 'Expected default');
});

test('Env::get() returns null for missing without default', function () {
    $val = Env::get('NONEXISTENT_KEY_XYZ');
    ok($val === null, 'Expected null');
});

test('Env::bool() returns true for "true"', function () {
    // Set env to true
    putenv('TEST_BOOL_TRUE=true');
    ok(Env::bool('TEST_BOOL_TRUE') === true, 'Expected true');
});

test('Env::bool() returns false for "false"', function () {
    putenv('TEST_BOOL_FALSE=false');
    ok(Env::bool('TEST_BOOL_FALSE') === false, 'Expected false');
});

test('Env::bool() returns default for missing', function () {
    ok(Env::bool('NONEXISTENT_BOOL', true) === true, 'Expected default true');
    ok(Env::bool('NONEXISTENT_BOOL', false) === false, 'Expected default false');
});

test('Env::load() skips missing file', function () {
    Env::load('/tmp/nonexistent_env_file.env');
    ok(true, 'No error for missing file');
});

test('Env::load() skips comments', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'env_');
    file_put_contents($tmp, "# Comment\nKEY=value\n# Another comment");
    Env::load($tmp);
    $val = Env::get('KEY');
    ok($val === 'value', 'Expected value');
    unlink($tmp);
});

// ─── Resource ──────────────────────────────────

echo "\n--- Resource ---\n";

// Concrete resource for testing
final class TestUserResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'name' => $this->data['name'] ?? null,
        ];
    }
}

test('Resource::make() transforms array', function () {
    $result = TestUserResource::make(['id' => 1, 'name' => 'Alice', 'age' => 25]);
    ok(is_array($result), 'Expected array');
    ok($result['id'] === 1, 'Expected id=1');
    ok($result['name'] === 'Alice', 'Expected name=Alice');
    ok(!isset($result['age']), 'Expected no age (filtered)');
});

test('Resource::make() with field filter', function () {
    $result = TestUserResource::make(['id' => 1, 'name' => 'Bob', 'email' => 'b@b.com'], ['name', 'email']);
    ok(is_array($result), 'Expected array');
    ok(isset($result['name']), 'Expected name');
    ok(isset($result['email']), 'Expected email');
    ok(!isset($result['id']), 'Expected no id');
});

test('Resource::collection() transforms array of arrays', function () {
    $items = [
        ['id' => 1, 'name' => 'A'],
        ['id' => 2, 'name' => 'B'],
    ];
    $result = TestUserResource::collection($items);
    ok(count($result) === 2, 'Expected 2 items');
    ok($result[0]['name'] === 'A', 'Expected name=A');
});

test('Resource::collection() handles empty array', function () {
    $result = TestUserResource::collection([]);
    ok(is_array($result), 'Expected array');
    ok(count($result) === 0, 'Expected 0 items');
});

test('Resource::collectionOf() filters fields', function () {
    $items = [
        ['id' => 1, 'name' => 'X', 'age' => 20],
        ['id' => 2, 'name' => 'Y', 'age' => 30],
    ];
    $result = TestUserResource::collectionOf($items, ['name']);
    ok(count($result) === 2, 'Expected 2 items');
    ok(isset($result[0]['name']), 'Expected name');
    ok(!isset($result[0]['id']), 'Expected no id');
    ok(!isset($result[0]['age']), 'Expected no age');
});

// ─── DB facade ─────────────────────────────────

echo "\n--- DB Facade ---\n";

test('DB::table() returns QueryBuilder', function () {
    $qb = DB::table('users');
    ok($qb !== null, 'Expected QueryBuilder');
    ok(method_exists($qb, 'get'), 'Expected get() method');
});

// ─── ValidationException ───────────────────────

echo "\n--- ValidationException ---\n";

test('ValidationException stores errors', function () {
    $e = new ValidationException(['email' => ['Required']], 'Custom message');
    ok($e->getMessage() === 'Custom message', 'Expected custom message');
    ok($e->getCode() === 422, 'Expected 422 code');
    $errors = $e->errors();
    ok(isset($errors['email']), 'Expected email error');
    ok($errors['email'][0] === 'Required', 'Expected Required');
});

test('ValidationException toResponse() returns 422 Response', function () {
    $e = new ValidationException(['name' => ['Min 3 chars']]);
    $r = $e->toResponse();
    ok($r instanceof Response, 'Expected Response');
    ok($r->statusCode() === 422, 'Expected 422');
    $p = $r->payload();
    ok($p['success'] === false, 'Expected success=false');
});

// ─── Route ─────────────────────────────────────

echo "\n--- Route Fluent Interface ---\n";

test('Route::middleware() chains correctly', function () {
    $router = new Router();
    $route = $router->get('/test-middleware', fn () => Response::success());
    $result = $route->middleware(['auth']);
    ok($result === $route, 'Expected fluent return');
});

test('Route::cache() chains correctly', function () {
    $router = new Router();
    $route = $router->get('/test-cache', fn () => Response::success());
    $result = $route->cache(120);
    ok($result === $route, 'Expected fluent return');
});

test('Route::throttle() chains correctly', function () {
    $router = new Router();
    $route = $router->get('/test-throttle', fn () => Response::success());
    $result = $route->throttle(60, 1);
    ok($result === $route, 'Expected fluent return');
});

// ─── Results ───────────────────────────────────

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
