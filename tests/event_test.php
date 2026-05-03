<?php

declare(strict_types=1);

/**
 * Comprehensive test for Event system and Model CRUD hooks.
 *
 * Run: php tests/event_test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Siro\Core\App;
use Siro\Core\Database;
use Siro\Core\Event;
use Siro\Core\Model;

// ─── Test Model (defined early for autoloading) ──────────
final class TestUser extends Model
{
    protected string $table = 'event_test_users';
    protected array $fillable = ['name', 'email'];
}

$basePath = dirname(__DIR__);
define('SIRO_BASE_PATH', $basePath);

$app = new App($basePath);
$app->boot();

require_once __DIR__ . '/db_test_helper.php';

// Setup test table
$pdo = Database::connection();
$pdo->exec("
    CREATE TABLE IF NOT EXISTS event_test_users (
        id " . db_id_col() . ",
        name TEXT NOT NULL,
        email TEXT NOT NULL
    )
");
$pdo->exec("DELETE FROM event_test_users");

echo "=== Event System & Model CRUD Hooks Test ===\n\n";

$passed = 0;
$failed = 0;

function test(string $name, callable $fn): void
{
    global $passed, $failed;
    try {
        $fn();
        echo "  \xE2\x9C\x93 {$name}\n";
        $passed++;
    } catch (Throwable $e) {
        echo "  \xE2\x9C\x97 {$name}: {$e->getMessage()}\n";
        echo "    in {$e->getFile()}:{$e->getLine()}\n";
        $failed++;
    }
}

function assertTrue(mixed $value, string $msg = ''): void
{
    if ($value !== true) {
        throw new RuntimeException($msg ?: 'Expected true, got ' . gettype($value));
    }
}

function assertEquals(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($msg ?: 'Expected ' . json_encode($expected) . ', got ' . json_encode($actual));
    }
}

function assertNull(mixed $value, string $msg = ''): void
{
    if ($value !== null) {
        throw new RuntimeException($msg ?: 'Expected null, got ' . json_encode($value));
    }
}

// ─── Event Tests ─────────────────────────────────────────
echo "--- Event: Basic Dispatch ---\n";

test('Event::on() registers listener', function (): void {
    Event::flush();
    $fired = false;
    Event::on('test.event', function () use (&$fired): void { $fired = true; });
    Event::emit('test.event');
    assertTrue($fired, 'Listener should fire');
});

test('Event::emit() passes payload', function (): void {
    Event::flush();
    $result = null;
    Event::on('test.payload', function ($payload) use (&$result): void { $result = $payload; });
    Event::emit('test.payload', ['key' => 'value']);
    assertEquals('value', $result['key'] ?? null);
});

test('Event::once() fires only once', function (): void {
    Event::flush();
    $count = 0;
    Event::once('test.once', function () use (&$count): void { $count++; });
    Event::emit('test.once');
    Event::emit('test.once');
    assertEquals(1, $count, 'Should fire only once');
});

test('Event::off() removes listeners', function (): void {
    Event::flush();
    $fired = false;
    Event::on('test.remove', function () use (&$fired): void { $fired = true; });
    Event::off('test.remove');
    Event::emit('test.remove');
    assertTrue(!$fired, 'Should not fire after removal');
});

test('Event::off() with wildcard', function (): void {
    Event::flush();
    $fired = false;
    Event::on('user.created', function () use (&$fired): void { $fired = true; });
    Event::on('user.updated', function (): void { /* noop */ });
    Event::off('user.*');
    Event::emit('user.created');
    assertTrue(!$fired, 'Should not fire after wildcard removal');
});

test('Event::hasListeners() checks correctly', function (): void {
    Event::flush();
    assertTrue(!Event::hasListeners('test.any'));
    Event::on('test.any', function (): void { /* noop */ });
    assertTrue(Event::hasListeners('test.any'));
});

test('Event::emit() with wildcard listener', function (): void {
    Event::flush();
    $matched = [];
    Event::on('user.created', function ($payload) use (&$matched): void { $matched[] = 'created'; });
    Event::on('user.updated', function ($payload) use (&$matched): void { $matched[] = 'updated'; });
    Event::on('user.*', function ($payload) use (&$matched): void { $matched[] = 'wildcard'; });
    Event::emit('user.created', ['id' => 1]);
    assertEquals(['created', 'wildcard'], $matched, 'Both exact and wildcard should fire');
});

test('Event::flush() clears all', function (): void {
    Event::on('test.a', function (): void { /* noop */ });
    Event::on('test.b', function (): void { /* noop */ });
    Event::flush();
    assertTrue(!Event::hasListeners('test.a'));
    assertTrue(!Event::hasListeners('test.b'));
});

echo "\n--- Event: Cancellation ---\n";

test('Event listener returning false cancels', function (): void {
    Event::flush();
    $secondFired = false;
    Event::on('test.cancel', function (): bool { return false; });
    Event::on('test.cancel', function () use (&$secondFired): void { $secondFired = true; });
    $result = Event::emit('test.cancel');
    assertTrue(!$result, 'emit() should return false when cancelled');
    assertTrue(!$secondFired, 'Subsequent listeners should not fire');
});

echo "\n--- Model CRUD Events ---\n";

test('Model::create() fires creating + created', function (): void {
    Event::flush();
    $events = [];
    Event::on('event_test_users.creating', function ($model) use (&$events): void {
        $events[] = 'creating';
        assertTrue($model instanceof \Siro\Core\Model);
    });
    Event::on('event_test_users.created', function ($model) use (&$events): void {
        $events[] = 'created';
    });
    Event::on('event_test_users.saving', function ($model) use (&$events): void {
        $events[] = 'saving';
    });
    Event::on('event_test_users.saved', function ($model) use (&$events): void {
        $events[] = 'saved';
    });

    $user = TestUser::create(['name' => 'John', 'email' => 'john@test.com']);
    assertEquals(['saving', 'creating', 'created', 'saved'], $events);
    assertTrue($user->id !== null, 'Should have ID after create');
});

test('Model::save() (update) fires updating + updated', function (): void {
    Event::flush();
    $events = [];
    Event::on('event_test_users.updating', function () use (&$events): void { $events[] = 'updating'; });
    Event::on('event_test_users.updated', function () use (&$events): void { $events[] = 'updated'; });
    Event::on('event_test_users.saving', function () use (&$events): void { $events[] = 'saving'; });
    Event::on('event_test_users.saved', function () use (&$events): void { $events[] = 'saved'; });

    $user = TestUser::find(1);
    $user->name = 'Jane';
    $user->save();
    assertEquals(['saving', 'updating', 'updated', 'saved'], $events);
});

test('Model::delete() fires deleting + deleted', function (): void {
    Event::flush();
    $events = [];
    Event::on('event_test_users.deleting', function () use (&$events): void { $events[] = 'deleting'; });
    Event::on('event_test_users.deleted', function () use (&$events): void { $events[] = 'deleted'; });

    $user = TestUser::find(1);
    $user->delete();
    assertEquals(['deleting', 'deleted'], $events);
});

test('Model creating event can cancel (return false)', function (): void {
    Event::flush();
    Event::on('event_test_users.creating', function (): bool { return false; });

    $user = TestUser::create(['name' => 'Blocked', 'email' => 'blocked@test.com']);
    assertNull($user->id, 'Should not have ID when creating cancelled');
});

test('Model deleting event can cancel (return false)', function (): void {
    Event::flush();
    // Create via raw DB to avoid events
    $id = Database::table('event_test_users')->insert([
        'name' => 'Keep',
        'email' => 'keep@test.com'
    ]);

    Event::on('event_test_users.deleting', function (): bool { return false; });

    $user = TestUser::find($id);
    $result = $user->delete();
    assertTrue(!$result, 'delete() should return false when cancelled');

    // Verify still exists
    $stillExists = TestUser::find($id);
    assertTrue($stillExists !== null, 'User should still exist');
});

test('Multiple listeners on same event', function (): void {
    Event::flush();
    $order = [];
    Event::on('event_test_users.created', function () use (&$order): void { $order[] = 'a'; });
    Event::on('event_test_users.created', function () use (&$order): void { $order[] = 'b'; });

    TestUser::create(['name' => 'Multi', 'email' => 'multi@test.com']);
    assertEquals(['a', 'b'], $order);
});

// Cleanup
$pdo->exec("DROP TABLE IF EXISTS event_test_users");

// ─── Summary ─────────────────────────────────────────────
echo "\n=== Results ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

exit($failed > 0 ? 1 : 0);
