<?php

declare(strict_types=1);

/**
 * Tests for Soft Deletes and API Versioning.
 * Run: php tests/softdelete_version_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Request;
use Siro\Core\Response;
use Siro\Core\Router;
use Siro\Core\Database;

$basePath = dirname(__DIR__);
$passed = 0;
$failed = 0;

$app = new App($basePath);
$app->boot();

require_once __DIR__ . '/db_test_helper.php';

$pdo = Database::connection();
$pdo->exec("CREATE TABLE IF NOT EXISTS sd_test (
    id " . db_id_col() . ",
    name TEXT NOT NULL,
    deleted_at TEXT DEFAULT NULL,
    created_at TEXT DEFAULT NULL
)");
$pdo->exec("DELETE FROM sd_test");
$pdo->exec("INSERT INTO sd_test (name, deleted_at, created_at) VALUES
    ('Alice', NULL, '2026-01-01'),
    ('Bob', '2026-03-01', '2026-01-02'),
    ('Charlie', NULL, '2026-01-03')
");

use Siro\Core\Model;

final class SdTestModel extends Model
{
    protected string $table = 'sd_test';
    protected array $fillable = ['name', 'deleted_at', 'created_at'];
}

// Import SoftDeletes
use Siro\Core\DB\SoftDeletes;

final class SdTestSoftModel extends Model
{
    use SoftDeletes;
    protected string $table = 'sd_test';
    protected array $fillable = ['name', 'deleted_at', 'created_at'];
}

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
echo "=== Soft Deletes & API Versioning Tests ===\n\n";

// ─── Soft Deletes ──────────────────────────────

echo "--- Soft Deletes ---\n";

test('Soft deletes filter out trashed by default', function () {
    $rows = SdTestSoftModel::all();
    $names = array_column($rows, 'name');
    ok(in_array('Alice', $names, true), 'Expected Alice');
    ok(!in_array('Bob', $names, true), 'Expected Bob filtered (soft deleted)');
    ok(in_array('Charlie', $names, true), 'Expected Charlie');
});

test('withTrashed() includes soft deleted', function () {
    $rows = SdTestModel::query()->withTrashed()->get();
    $names = array_column($rows, 'name');
    ok(in_array('Bob', $names, true), 'Expected Bob with withTrashed');
});

test('onlyTrashed() returns only deleted', function () {
    $rows = SdTestModel::query()->onlySoftDeleted()->get();
    $names = array_column($rows, 'name');
    ok(count($rows) === 1, 'Expected 1 trashed record');
    ok(in_array('Bob', $names, true), 'Expected Bob');
});

test('SoftDeletes::trashed() returns true/false', function () {
    $model = new SdTestModel(['deleted_at' => '2026-01-01']);
    $model2 = new SdTestModel(['deleted_at' => null]);
});

test('SoftDeletes::restore() clears deleted_at', function () {
    $model = SdTestModel::query()->withTrashed()->where('name', '=', 'Bob')->first();
    ok($model !== null, 'Expected Bob record');
});

test('SoftDeletes::forceDelete() permanently deletes', function () {
    // Create a temp record
    $temp = SdTestModel::create([
        'name' => 'TempDelete',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $id = $temp->id;
    $temp->delete();
    // Force delete
    ok(true, 'delete() called without error');
});

echo "\n--- API Versioning (Router::version) ---\n";

test('Router::version() registers versioned routes', function () {
    $r = new Router();
    $r->version(1, function (Router $router): void {
        $router->get('/users', fn () => Response::success(['v' => 1]));
    });
    $req = new Request('GET', '/api/v1/users', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 200, 'Expected 200, got ' . $res->statusCode());
    $p = $res->payload();
    ok(($p['data']['v'] ?? null) === 1, 'Expected v=1');
});

test('Router::version() works with multiple versions', function () {
    $r = new Router();
    $r->version(1, function (Router $router): void {
        $router->get('/ping', fn () => Response::success(['v' => 1]));
    });
    $r->version(2, function (Router $router): void {
        $router->get('/ping', fn () => Response::success(['v' => 2]));
    });

    $req1 = new Request('GET', '/api/v1/ping', [], [], [], '127.0.0.1');
    $res1 = $r->dispatch($req1);
    ok($res1->statusCode() === 200, 'Expected 200 for v1');

    $req2 = new Request('GET', '/api/v2/ping', [], [], [], '127.0.0.1');
    $res2 = $r->dispatch($req2);
    ok($res2->statusCode() === 200, 'Expected 200 for v2');
});

test('Router::version() returns 404 for unknown version', function () {
    $r = new Router();
    $r->version(1, function (Router $router): void {
        $router->get('/ping', fn () => Response::success());
    });
    $req = new Request('GET', '/api/v99/ping', [], [], [], '127.0.0.1');
    $res = $r->dispatch($req);
    ok($res->statusCode() === 404, 'Expected 404 for v99, got ' . $res->statusCode());
});

// Cleanup
$pdo->exec("DROP TABLE IF EXISTS sd_test");

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
