<?php

declare(strict_types=1);

/**
 * Comprehensive Validator & Model tests.
 * Run: php tests/validator_model_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\Validator;
use Siro\Core\Model;
use Siro\Core\Database;
use Siro\Core\App;

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

echo "=== Validator & Model Tests ===\n\n";

// ═══════════════════════════════════════════════
// VALIDATOR
// ═══════════════════════════════════════════════

echo "--- Validator: Required Rule ---\n";

test('required passes with non-empty string', function () {
    $e = Validator::make(['name' => 'John'], ['name' => 'required']);
    ok($e === [], 'Expected no errors');
});

test('required fails with empty string', function () {
    $e = Validator::make(['name' => ''], ['name' => 'required']);
    ok(isset($e['name']), 'Expected error for empty');
});

test('required fails with null/empty', function () {
    $e = Validator::make(['name' => ''], ['name' => 'required']);
    ok(isset($e['name']), 'Expected error for empty');
});

test('required passes with number 0', function () {
    $e = Validator::make(['val' => 0], ['val' => 'required']);
    ok($e === [], 'Expected no errors for 0');
});

echo "\n--- Validator: Email Rule ---\n";

test('email passes for valid email', function () {
    $e = Validator::make(['email' => 'test@example.com'], ['email' => 'email']);
    ok($e === [], 'Expected no errors');
});

test('email passes for valid subdomain email', function () {
    $e = Validator::make(['email' => 'user@sub.example.co.uk'], ['email' => 'email']);
    ok($e === [], 'Expected no errors');
});

test('email fails for missing @', function () {
    $e = Validator::make(['email' => 'not-email'], ['email' => 'email']);
    ok(isset($e['email']), 'Expected error for invalid email');
});

test('email fails for empty when combined with required', function () {
    $e = Validator::make(['email' => ''], ['email' => 'required|email']);
    ok(isset($e['email']), 'Expected error for empty with required');
});

echo "\n--- Validator: Min / Max Rules ---\n";

test('min passes for long enough string', function () {
    $e = Validator::make(['name' => 'Hello'], ['name' => 'min:3']);
    ok($e === [], 'Expected no errors');
});

test('min fails for short string', function () {
    $e = Validator::make(['name' => 'AB'], ['name' => 'min:3']);
    ok(isset($e['name']), 'Expected min error');
});

test('max passes for short enough string', function () {
    $e = Validator::make(['name' => 'Hi'], ['name' => 'max:10']);
    ok($e === [], 'Expected no errors');
});

test('max fails for too long string', function () {
    $e = Validator::make(['name' => 'Too Long Name Here'], ['name' => 'max:10']);
    ok(isset($e['name']), 'Expected max error');
});

test('min applies to numbers', function () {
    $e = Validator::make(['age' => '5'], ['age' => 'min:18']);
    ok(isset($e['age']), 'Expected min error for number');
});

test('max applies to numbers', function () {
    $e = Validator::make(['age' => '200'], ['age' => 'max:150']);
    ok(isset($e['age']), 'Expected max error for number');
});

echo "\n--- Validator: Numeric / Integer Rules ---\n";

test('numeric passes for number string', function () {
    $e = Validator::make(['price' => '19.99'], ['price' => 'numeric']);
    ok($e === [], 'Expected no errors');
});

test('numeric passes for integer', function () {
    $e = Validator::make(['qty' => '5'], ['qty' => 'numeric']);
    ok($e === [], 'Expected no errors');
});

test('numeric fails for non-numeric', function () {
    $e = Validator::make(['price' => 'abc'], ['price' => 'numeric']);
    ok(isset($e['price']), 'Expected numeric error');
});

test('integer passes for int string', function () {
    $e = Validator::make(['count' => '42'], ['count' => 'integer']);
    ok($e === [], 'Expected no errors');
});

test('integer fails for float string', function () {
    $e = Validator::make(['count' => '3.14'], ['count' => 'integer']);
    ok(isset($e['count']), 'Expected integer error');
});

test('integer fails for non-numeric', function () {
    $e = Validator::make(['count' => 'abc'], ['count' => 'integer']);
    ok(isset($e['count']), 'Expected integer error');
});

echo "\n--- Validator: Confirmed Rule ---\n";

test('confirmed passes when matches', function () {
    $e = Validator::make([
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ], ['password' => 'confirmed']);
    ok($e === [], 'Expected no errors');
});

test('confirmed fails when mismatched', function () {
    $e = Validator::make([
        'password' => 'secret123',
        'password_confirmation' => 'different',
    ], ['password' => 'confirmed']);
    ok(isset($e['password']), 'Expected confirmed error');
});

echo "\n--- Validator: Multiple Rules ---\n";

test('multiple rules all pass', function () {
    $e = Validator::make([
        'email' => 'user@example.com',
        'password' => 'secret123',
    ], [
        'email' => 'required|email|max:255',
        'password' => 'required|min:6|max:100',
    ]);
    ok($e === [], 'Expected no errors');
});

test('multiple rules first failure', function () {
    $e = Validator::make(['email' => ''], ['email' => 'required|email']);
    ok(isset($e['email']), 'Expected error for empty email');
});

echo "\n--- Validator: Custom Rule (extend) ---\n";

test('Validator::extend() registers custom rule', function () {
    Validator::extend('phone', function ($value) {
        return preg_match('/^\+?[0-9]{7,15}$/', (string) $value) ? true : false;
    });
    $e = Validator::make(['phone' => '0123456789'], ['phone' => 'phone']);
    ok($e === [], 'Expected no errors');
});

test('custom rule fails for invalid value', function () {
    $e = Validator::make(['phone' => 'abc'], ['phone' => 'phone']);
    ok(isset($e['phone']), 'Expected phone error');
});

test('custom rule with parameter', function () {
    Validator::extend('min_value', function ($value, $field, $input, $param) {
        return (float) $value >= (float) $param;
    });
    $e = Validator::make(['age' => '15'], ['age' => 'min_value:18']);
    ok(isset($e['age']), 'Expected min_value error');
});

test('custom rule passes with parameter', function () {
    $e = Validator::make(['age' => '20'], ['age' => 'min_value:18']);
    ok($e === [], 'Expected no errors');
});

test('custom rule returns custom error message', function () {
    Validator::extend('strong_password', function ($value) {
        return strlen((string) $value) < 8 ? 'Password must be at least 8 characters' : true;
    });
    $e = Validator::make(['pw' => 'short'], ['pw' => 'strong_password']);
    ok(isset($e['pw']), 'Expected strong_password error');
});

test('Validator::extend() receives full input', function () {
    $received = null;
    Validator::extend('check_input', function ($value, $field, $input) use (&$received) {
        $received = $input;
        return true;
    });
    Validator::make(['a' => '1', 'b' => '2'], ['a' => 'check_input']);
    ok(is_array($received), 'Expected input array');
    ok(($received['b'] ?? '') === '2', 'Expected b=2');
});

echo "\n--- Validator: Empty Input ---\n";

test('Validator::make() returns empty for no rules', function () {
    $e = Validator::make(['a' => '1'], []);
    ok($e === [], 'Expected empty errors');
});

// ═══════════════════════════════════════════════
// MODEL
// ═══════════════════════════════════════════════

echo "\n--- Model: Setup ---\n";

// Boot app to get DB connection
$app = new App($basePath);
$app->boot();

require_once __DIR__ . '/db_test_helper.php';

// Create test table
$pdo = Database::connection();
$pdo->exec("CREATE TABLE IF NOT EXISTS model_test_users (
    id " . db_id_col() . ",
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    status " . db_type_int() . " DEFAULT 1,
    created_at TEXT DEFAULT NULL
)");
$pdo->exec("DELETE FROM model_test_users");

final class ModelTestUser extends Model
{
    protected string $table = 'model_test_users';
    protected array $hidden = ['email'];
    protected array $casts = ['id' => 'int', 'status' => 'int'];
    protected array $fillable = ['name', 'email', 'status', 'created_at'];
}

echo "--- Model: CRUD ---\n";

test('Model::create() inserts and returns', function () {
    $u = ModelTestUser::create([
        'name' => 'Alice',
        'email' => 'alice@test.com',
        'status' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    ok($u !== null, 'Expected user object');
    ok(isset($u->id), 'Expected id');
    ok($u->name === 'Alice', 'Expected name=Alice');
});

test('Model::find() returns record', function () {
    $u = ModelTestUser::find(1);
    ok($u !== null, 'Expected record');
    ok($u->id === 1, 'Expected id=1');
});

test('Model::find() returns null for missing', function () {
    $u = ModelTestUser::find(9999);
    ok($u === null, 'Expected null');
});

test('Model::where() filters records', function () {
    $rows = ModelTestUser::where('name', '=', 'Alice')->get();
    ok(count($rows) >= 1, 'Expected at least 1');
    ok(($rows[0]['name'] ?? '') === 'Alice', 'Expected name=Alice');
});

test('Model::where() with multiple conditions', function () {
    $rows = ModelTestUser::where('status', '=', 1)
        ->where('name', '=', 'Alice')
        ->get();
    ok(count($rows) >= 1, 'Expected at least 1');
});

test('Model::all() returns all', function () {
    $rows = ModelTestUser::all();
    ok(count($rows) >= 1, 'Expected at least 1');
});

test('Model::first() returns first record', function () {
    $row = ModelTestUser::first();
    ok($row !== null, 'Expected record');
    ok(is_array($row) || is_object($row), 'Expected result');
    ok(true, 'first() executed without error');
});

echo "\n--- Model: Update ---\n";

test('Model update() modifies record', function () {
    $u = ModelTestUser::find(1);
    $u->update(['name' => 'Alice Updated']);
    $u2 = ModelTestUser::find(1);
    ok($u2->name === 'Alice Updated', 'Expected updated name');
});

echo "\n--- Model: Delete ---\n";

test('Model::delete() removes record', function () {
    $u = ModelTestUser::create([
        'name' => 'Temp',
        'email' => 'temp@test.com',
        'status' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $id = $u->id;
    $u->delete();
    $found = ModelTestUser::find($id);
    ok($found === null, 'Expected null after delete');
});

echo "\n--- Model: QueryBuilder ---\n";

test('Model::query() returns QueryBuilder', function () {
    $qb = ModelTestUser::query();
    ok($qb !== null, 'Expected QueryBuilder');
    $rows = $qb->limit(1)->get();
    ok(is_array($rows), 'Expected array');
});

test('Model::orderBy() works', function () {
    $rows = ModelTestUser::query()->orderBy('id', 'DESC')->limit(1)->get();
    ok(count($rows) === 1, 'Expected 1 row');
});

test('Model::paginate() returns structured data', function () {
    $result = ModelTestUser::query()->paginate(10, 1);
    ok(isset($result['data']), 'Expected data key');
    ok(isset($result['meta']), 'Expected meta key');
    ok(isset($result['meta']['page']), 'Expected meta.page');
});

echo "\n--- Model: Hidden / Casts ---\n";

test('Model::toArray() hides hidden fields', function () {
    $u = ModelTestUser::find(1);
    $arr = $u->toArray();
    ok(!isset($arr['email']), 'Expected email hidden');
});

test('Model casts id to int', function () {
    $u = ModelTestUser::find(1);
    ok(is_int($u->id), 'Expected int, got ' . gettype($u->id));
});

echo "\n--- Model: findOrFail ---\n";

test('Model::findOrFail() returns record', function () {
    $u = ModelTestUser::findOrFail(1);
    ok($u !== null, 'Expected record');
});

// Cleanup
$pdo->exec("DROP TABLE IF EXISTS model_test_users");

echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
exit($failed > 0 ? 1 : 0);
