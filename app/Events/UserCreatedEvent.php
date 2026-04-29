<?php

declare(strict_types=1);

namespace App\Events;

use Siro\Core\Event;

/**
 * UserCreatedEvent — generated event class.
 *
 * Usage:
 *   Event::on('user_created_event', function ($payload) {
 *       // handle event
 *   });
 *
 *   // Or dispatch directly:
 *   UserCreatedEvent::dispatch($payload);
 *
 * @package App\Events
 */
final class UserCreatedEvent
{
    /**
     * Dispatch the event.
     */
    public static function dispatch(mixed $payload = null): void
    {
        Event::emit('user_created_event', $payload);
    }

    /**
     * Register a listener for this event.
     */
    public static function listen(callable $callback): void
    {
        Event::on('user_created_event', $callback);
    }
}
