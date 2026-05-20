# Events API Reference

## Overview

Siro's event system provides pub/sub communication with wildcard pattern matching and one-time listeners.

```php
use Siro\Core\Event;
```

---

## Defining Events

```php
<?php

declare(strict_types=1);

namespace App\Events;

final class UserCreatedEvent
{
    public function __construct(
        public readonly int $userId,
        public readonly string $email,
    ) {}
}
```

---

## Dispatching Events

```php
// Dispatch with payload
Event::dispatch(new UserCreatedEvent($user->id, $user->email));

// Dispatch with string name + payload
Event::dispatch('user.created', ['user_id' => $user->id, 'email' => $user->email]);

// Dispatch with wildcard
Event::dispatch('user.*', $data);
```

---

## Listening to Events

### In `routes/api.php`

```php
use App\Events\UserCreatedEvent;
use App\Listeners\SendWelcomeEmailListener;

// Class-based listener
Event::listen(UserCreatedEvent::class, SendWelcomeEmailListener::class);

// Closure listener
Event::listen(UserCreatedEvent::class, function (UserCreatedEvent $event): void {
    Mail::to($event->email)->send(new WelcomeMail($event->userId));
});

// Wildcard listener
Event::listen('user.*', function (string $event, array $data): void {
    Logger::info("User event: {$event}", $data);
});
```

---

## Listener Classes

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreatedEvent;

final class SendWelcomeEmailListener
{
    public function handle(UserCreatedEvent $event): void
    {
        // Send welcome email
        Mail::to($event->email)->send(new WelcomeMail($event->userId));
    }
}
```

---

## Generate Listener

```bash
php siro make:listener SendWelcomeEmail
```

---

## One-Time Listeners

```php
// Listener runs once, then removed
Event::listenOnce('user.created', function ($event): void {
    Logger::info('First user created');
});
```

---

## Stopping Propagation

Return `false` from a listener to stop event propagation:

```php
Event::listen('user.created', function ($event): void {
    if ($this->shouldBlock()) {
        return false; // Stops further listeners
    }
});
```

---

## Model Events

Models automatically dispatch lifecycle events:

```php
// Available model events
Event::listen('model.creating', function ($model): void {});
Event::listen('model.created', function ($model): void {});
Event::listen('model.saving', function ($model): void {});
Event::listen('model.saved', function ($model): void {});
Event::listen('model.updating', function ($model): void {});
Event::listen('model.updated', function ($model): void {});
Event::listen('model.deleting', function ($model): void {});
Event::listen('model.deleted', function ($model): void {});
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `dispatch(object\|string $event, mixed $payload)` | Dispatch event |
| `listen(string $event, callable\|string $listener)` | Register listener |
| `listenOnce(string $event, callable $listener)` | Register one-time listener |
| `removeListener(string $event, callable $listener)` | Remove listener |
| `getListeners(string $event)` | Get all listeners for event |
| `hasListeners(string $event)` | Check if event has listeners |
| `flush()` | Remove all listeners |
