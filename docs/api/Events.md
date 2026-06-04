---
title: E ve nt s
description: SiroPHP E ve nt s reference
sidebar_position: 8
sidebar_label: E ve nt s
---

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
Event::emit(new UserCreatedEvent($user->id, $user->email));

// Dispatch with string name + payload
Event::emit('user.created', ['user_id' => $user->id, 'email' => $user->email]);

// Dispatch with wildcard
Event::emit('user.*', $data);
```

---

## Listening to Events

### In `routes/api.php`

```php
use App\Events\UserCreatedEvent;
use App\Listeners\SendWelcomeEmailListener;

// Class-based listener
Event::on(UserCreatedEvent::class, SendWelcomeEmailListener::class);

// Closure listener
Event::on(UserCreatedEvent::class, function (UserCreatedEvent $event): void {
    Mail::to($event->email)->send(new WelcomeMail($event->userId));
});

// Wildcard listener
Event::on('user.*', function (string $event, array $data): void {
    Logger::debug("User event: {$event}", $data);
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
Event::once('user.created', function ($event): void {
    Logger::debug('First user created');
});
```

---

## Stopping Propagation

Return `false` from a listener to stop event propagation:

```php
Event::on('user.created', function ($event): void {
    if ($this->shouldBlock()) {
        return false; // Stops further listeners
    }
});
```

---

## Model Events

Models automatically dispatch lifecycle events:

```php
// Available model events (replace `{table}` with your model's table name)
Event::on('{table}.creating', function ($model): void {});
Event::on('{table}.created', function ($model): void {});
Event::on('{table}.saving', function ($model): void {});
Event::on('{table}.saved', function ($model): void {});
Event::on('{table}.updating', function ($model): void {});
Event::on('{table}.updated', function ($model): void {});
Event::on('{table}.deleting', function ($model): void {});
Event::on('{table}.deleted', function ($model): void {});
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `emit(object\|string $event, mixed $payload)` | Emit event |
| `on(string $event, callable\|string $listener)` | Register listener |
| `once(string $event, callable $listener)` | Register one-time listener |
| `off(string $event)` | Remove all listeners for event |
| `hasListeners(string $event)` | Check if event has listeners |
| `currentEvent()` | Get name of currently firing event |
| `flush()` | Remove all listeners |
