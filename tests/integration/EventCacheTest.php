<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\TestCase;
use Siro\Core\Event;
use Siro\Core\Cache;

final class EventCacheTest extends TestCase
{
    public function testEventOnAndEmit(): void
    {
        $fired = false;
        Event::on('test.event', function () use (&$fired) { $fired = true; });
        Event::emit('test.event');
        $this->assertTrue($fired);
    }

    public function testEventOnce(): void
    {
        $count = 0;
        Event::once('test.once', function () use (&$count) { $count++; });
        Event::emit('test.once');
        Event::emit('test.once');
        $this->assertEquals(1, $count);
    }

    public function testEventWithPayload(): void
    {
        $result = null;
        Event::on('test.payload', function ($p) use (&$result) { $result = $p; });
        Event::emit('test.payload', 'hello');
        $this->assertEquals('hello', $result);
    }

    public function testEventWildcard(): void
    {
        $events = [];
        Event::on('users.*', function ($p) use (&$events) {
            $events[] = Event::currentEvent();
        });
        Event::emit('users.created', 1);
        Event::emit('users.updated', 1);
        $this->assertCount(2, $events);
    }

    public function testEventOff(): void
    {
        $fired = false;
        Event::on('test.off', function () use (&$fired) { $fired = true; });
        Event::off('test.off');
        Event::emit('test.off');
        $this->assertFalse($fired);
    }

    public function testEventHasListeners(): void
    {
        Event::on('test.has', function () {});
        $this->assertTrue(Event::hasListeners('test.has'));
    }

    public function testEventFlush(): void
    {
        Event::on('test.flush', function () {});
        Event::flush();
        $this->assertFalse(Event::hasListeners('test.flush'));
    }

    public function testEventCancellation(): void
    {
        Event::on('test.cancel', function () { return false; });
        $result = Event::emit('test.cancel');
        $this->assertFalse($result);
    }

    public function testCacheSetAndGet(): void
    {
        Cache::set('test_key', 'test_value');
        $this->assertEquals('test_value', Cache::get('test_key'));
    }

    public function testCacheGetMissing(): void
    {
        $this->assertNull(Cache::get('nonexistent_key_' . uniqid()));
    }

    public function testCacheHas(): void
    {
        Cache::set('test_has', 'val');
        $this->assertTrue(Cache::has('test_has'));
    }

    public function testCacheForget(): void
    {
        Cache::set('test_forget', 'val');
        Cache::forget('test_forget');
        $this->assertNull(Cache::get('test_forget'));
    }

    public function testCacheRemember(): void
    {
        $result = Cache::remember('test_rem', 60, function () { return 'cached'; });
        $this->assertEquals('cached', $result);
    }

    public function testCacheFlush(): void
    {
        Cache::set('test_flush', 'val');
        Cache::flush();
        $this->assertNull(Cache::get('test_flush'));
    }

    public function testCacheRequestStatusIsArray(): void
    {
        $status = Cache::requestStatus();
        $this->assertIsArray($status);
    }

    public function testCacheResetRequestState(): void
    {
        Cache::set('test_reset', 'val');
        Cache::resetRequestState();
        $this->assertIsArray(Cache::requestStatus());
    }

    public function testCacheSetWithZeroTtl(): void
    {
        Cache::set('test_zero', 'val', 0);
        $this->assertEquals('val', Cache::get('test_zero'));
    }

    public function testSessionBasicFlow(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Session::class));
    }

    public function testCollectionBasic(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Collection::class));
    }

    public function testHashBasic(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Hash::class));
    }

    public function testEncrypterBasic(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Encrypter::class));
    }

    public function testHttpBasic(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Http::class));
    }

    public function testStorageBasic(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Storage::class));
    }

    public function testQueueBasic(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Queue::class));
    }

    public function testMailBasic(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Mail::class));
    }

    public function testAppClassExists(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\App::class));
    }

    public function testRouterClassExists(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Router::class));
    }

    public function testRequestClassExists(): void
    {
        $this->assertTrue(class_exists(\Siro\Core\Request::class));
    }

    public function testEventCurrentEvent(): void
    {
        Event::on('test.ce', function () {
            $this->assertEquals('test.ce', Event::currentEvent());
        });
        Event::emit('test.ce');
    }
}
