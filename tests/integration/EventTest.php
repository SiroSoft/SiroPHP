<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use Siro\Core\Event;
use Siro\Core\Database;
use Siro\Core\Model;

final class EventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createApp();
        Event::flush();
        $pdo = Database::connection();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS event_test_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL
            )
        ");
        $pdo->exec("DELETE FROM event_test_users");
    }

    protected function tearDown(): void
    {
        $pdo = Database::connection();
        $pdo->exec("DROP TABLE IF EXISTS event_test_users");
        Event::flush();
        parent::tearDown();
    }

    public function testEventOnAndEmitWork(): void
    {
        $fired = false;
        Event::on('test.event', function () use (&$fired) { $fired = true; });
        Event::emit('test.event');
        $this->assertTrue($fired);
    }

    public function testEventEmitPassesPayload(): void
    {
        $result = null;
        Event::on('test.payload', function ($data) use (&$result) { $result = $data['key']; });
        Event::emit('test.payload', ['key' => 'value']);
        $this->assertSame('value', $result);
    }

    public function testEventOnceFiresOnlyOnce(): void
    {
        $count = 0;
        Event::once('test.once', function () use (&$count) { $count++; });
        Event::emit('test.once');
        Event::emit('test.once');
        $this->assertEquals(1, $count);
    }

    public function testEventOffRemovesListeners(): void
    {
        $fired = false;
        Event::on('test.remove', function () use (&$fired) { $fired = true; });
        Event::off('test.remove');
        Event::emit('test.remove');
        $this->assertFalse($fired);
    }

    public function testEventFlushClearsAll(): void
    {
        Event::on('test.a', function () {});
        Event::on('test.b', function () {});
        Event::flush();
        $this->assertFalse(Event::hasListeners('test.a'));
        $this->assertFalse(Event::hasListeners('test.b'));
    }

    public function testEventListenerReturningFalseCancels(): void
    {
        $secondFired = false;
        Event::on('test.cancel', function (): bool { return false; });
        Event::on('test.cancel', function () use (&$secondFired) { $secondFired = true; });
        $result = Event::emit('test.cancel');
        $this->assertFalse($result);
        $this->assertFalse($secondFired);
    }

    public function testModelCreateFiresCrudEvents(): void
    {
        $events = [];

        Event::on('event_test_users.creating', function ($model) use (&$events) { $events[] = 'creating'; $this->assertInstanceOf(Model::class, $model); });
        Event::on('event_test_users.created', function () use (&$events) { $events[] = 'created'; });
        Event::on('event_test_users.saving', function () use (&$events) { $events[] = 'saving'; });
        Event::on('event_test_users.saved', function () use (&$events) { $events[] = 'saved'; });

        $model = new class extends Model {
            protected string $table = 'event_test_users';
            protected array $fillable = ['name', 'email'];
        };

        $user = $model->create(['name' => 'John', 'email' => 'john@test.com']);
        $this->assertNotNull($user->id);
        $this->assertSame(['saving', 'creating', 'created', 'saved'], $events);
    }

    public function testModelDeleteFiresDeleteEvents(): void
    {
        $events = [];
        $model = new class extends Model {
            protected string $table = 'event_test_users';
            protected array $fillable = ['name', 'email'];
        };

        $user = $model->create(['name' => 'Jane', 'email' => 'jane@test.com']);

        Event::on('event_test_users.deleting', function () use (&$events) { $events[] = 'deleting'; });
        Event::on('event_test_users.deleted', function () use (&$events) { $events[] = 'deleted'; });

        $user->delete();
        $this->assertSame(['deleting', 'deleted'], $events);
    }
}
