<?php

declare(strict_types=1);

/**
 * Comprehensive QueryBuilder & Database tests.
 * Run: php tests/querybuilder_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Database;
use Siro\Core\App;

$basePath = dirname(__DIR__);
$passed = 0;
$failed = 0;

// Boot for DB connection
$app = new App($basePath);
$app->boot();

$pdo = Database::connection();

// Setup test table
$pdo->exec("CREATE TABLE IF NOT EXISTS qb_test (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    age INTEGER DEFAULT 0,
    score REAL DEFAULT 0.0,
    status INTEGER DEFAULT 1,
    created_at TEXT DEFAULT NULL
)");
$pdo->exec("DELETE FROM qb_test");

// Insert test data
$pdo->exec("INSERT INTO qb_test (name, email, age, score, status, created_at) VALUES
    ('Alice', 'alice@test.com', 25, 85.5, 1, '2026-01-01'),
    ('Bob', 'bob@test.com', 30, 92.0, 1, '2026-01-02'),
    ('Charlie', 'charlie@test.com', 22, 78.0, 0, '2026-01-03'),
    ('Diana', 'diana@test.com', 28, 95.5, 1, '2026-01-04')
");

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

echo "=== QueryBuilder & Database Tests ===\n\n";

echo "--- QueryBuilder: Select ---\n";

test('QB select all returns all rows', function () {
    $rows = Database::table('qb_test')->get();
    ok(count($rows) === 4, 'Expected 4 rows, got ' . count($rows));
});

test('QB select specific columns', function () {
    $rows = Database::table('qb_test')->select(['name', 'email'])->get();
    ok(count($rows) === 4, 'Expected 4 rows');
    ok(isset($rows[0]['name']), 'Expected name');
    ok(!isset($rows[0]['age']), 'Expected no age');
});

test('QB first() returns single row', function () {
    $row = Database::table('qb_test')->first();
    ok($row !== null, 'Expected row');
    ok(isset($row['name']), 'Expected name');
});

echo "\n--- QueryBuilder: Where ---\n";

test('QB where = operator', function () {
    $rows = Database::table('qb_test')->where('name', '=', 'Alice')->get();
    ok(count($rows) === 1, 'Expected 1 row');
    ok(($rows[0]['email'] ?? '') === 'alice@test.com', 'Expected correct email');
});

test('QB where != operator', function () {
    $rows = Database::table('qb_test')->where('status', '!=', 0)->get();
    ok(count($rows) === 3, 'Expected 3 active users');
});

test('QB where > operator', function () {
    $rows = Database::table('qb_test')->where('age', '>', 25)->get();
    ok(count($rows) === 2, 'Expected 2 users over 25');
});

test('QB where < operator', function () {
    $rows = Database::table('qb_test')->where('age', '<', 25)->get();
    ok(count($rows) === 1, 'Expected 1 user under 25');
});

test('QB where >= operator', function () {
    $rows = Database::table('qb_test')->where('age', '>=', 28)->get();
    ok(count($rows) === 2, 'Expected 2 users');
});

test('QB where <= operator', function () {
    $rows = Database::table('qb_test')->where('age', '<=', 25)->get();
    ok(count($rows) === 2, 'Expected 2 users');
});

test('QB where LIKE operator', function () {
    $rows = Database::table('qb_test')->where('name', 'LIKE', '%lice')->get();
    ok(count($rows) >= 1, 'Expected at least 1');
});

test('QB multiple where conditions (AND)', function () {
    $rows = Database::table('qb_test')
        ->where('status', '=', 1)
        ->where('age', '>', 25)
        ->get();
    ok(count($rows) >= 1, 'Expected at least 1 active user over 25');
    ok(($rows[0]['name'] ?? '') !== '', 'Expected name');
});

echo "\n--- QueryBuilder: OrderBy, Limit, Offset ---\n";

test('QB orderBy ASC', function () {
    $rows = Database::table('qb_test')->orderBy('age', 'ASC')->get();
    ok((int) $rows[0]['age'] <= (int) $rows[1]['age'], 'Expected sorted ASC');
});

test('QB orderBy DESC', function () {
    $rows = Database::table('qb_test')->orderBy('age', 'DESC')->get();
    ok((int) $rows[0]['age'] >= (int) $rows[1]['age'], 'Expected sorted DESC');
});

test('QB limit returns N rows', function () {
    $rows = Database::table('qb_test')->limit(2)->get();
    ok(count($rows) === 2, 'Expected 2 rows, got ' . count($rows));
});

echo "\n--- QueryBuilder: Insert ---\n";

test('QB insert() returns lastInsertId', function () {
    $id = Database::table('qb_test')->insert([
        'name' => 'Eve',
        'email' => 'eve@test.com',
        'age' => 35,
        'score' => 88.0,
        'status' => 1,
        'created_at' => '2026-02-01',
    ]);
    ok($id > 0, 'Expected insert ID > 0, got ' . $id);
});

test('QB inserted data is retrievable', function () {
    $rows = Database::table('qb_test')->where('name', '=', 'Eve')->get();
    ok(count($rows) === 1, 'Expected 1 Eve');
    ok(($rows[0]['email'] ?? '') === 'eve@test.com', 'Expected Eve email');
});

echo "\n--- QueryBuilder: Update ---\n";

test('QB update() modifies rows', function () {
    $affected = Database::table('qb_test')
        ->where('name', '=', 'Eve')
        ->update(['age' => 36]);
    ok($affected >= 1, 'Expected at least 1 affected');
    $rows = Database::table('qb_test')->where('name', '=', 'Eve')->get();
    ok((int) $rows[0]['age'] === 36, 'Expected age=36');
});

echo "\n--- QueryBuilder: Delete ---\n";

test('QB delete() removes rows', function () {
    $affected = Database::table('qb_test')
        ->where('name', '=', 'Eve')
        ->delete();
    ok($affected >= 1, 'Expected at least 1 affected');
    $rows = Database::table('qb_test')->where('name', '=', 'Eve')->get();
    ok(count($rows) === 0, 'Expected no Eves');
});

echo "\n--- QueryBuilder: Count ---\n";

test('QB count() returns total', function () {
    $count = Database::table('qb_test')->count();
    ok(is_int($count) || is_numeric($count), 'Expected numeric count');
    ok((int) $count > 0, 'Expected count > 0');
});

echo "\n--- QueryBuilder: Paginate ---\n";

test('QB paginate() returns structured data', function () {
    $result = Database::table('qb_test')->paginate(2, 1);
    ok(isset($result['data']), 'Expected data key');
    ok(isset($result['meta']), 'Expected meta key');
    ok($result['meta']['page'] === 1, 'Expected page=1');
    ok($result['meta']['per_page'] === 2, 'Expected per_page=2');
    ok($result['meta']['total'] >= 4, 'Expected total >= 4');
    ok(count($result['data']) <= 2, 'Expected <= 2 items');
});

test('QB paginate page 2', function () {
    $result = Database::table('qb_test')->paginate(2, 2);
    ok($result['meta']['page'] === 2, 'Expected page=2');
    ok(count($result['data']) <= 2, 'Expected <= 2 items');
});

echo "\n--- QueryBuilder: Chaining ---\n";

test('QB fluent chaining where + orderBy + limit', function () {
    $rows = Database::table('qb_test')
        ->where('status', '=', 1)
        ->orderBy('score', 'DESC')
        ->limit(2)
        ->get();
    ok(count($rows) >= 1, 'Expected at least 1');
    ok((int) $rows[0]['status'] === 1, 'Expected active');
});

echo "\n--- Database: Connection ---\n";

test('Database::connection() returns PDO', function () {
    $conn = Database::connection();
    ok($conn instanceof PDO, 'Expected PDO instance');
});

test('Database::table() returns QueryBuilder', function () {
    $qb = Database::table('qb_test');
    ok($qb !== null, 'Expected QueryBuilder');
    ok(method_exists($qb, 'get'), 'Expected get() method');
});

// Cleanup
$pdo->exec("DROP TABLE IF EXISTS qb_test");

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
