# Event System Guide

## Basics

The event system provides a publish-subscribe pattern for decoupled communication.

```php
use Siro\Core\Event;

// Register a listener
Event::on('user.registered', function (array $payload) {
    // Handle the event
    $userId = $payload['id'];
    Log::info("User registered: $userId");
});

// Emit an event
Event::emit('user.registered', ['id' => 1, 'email' => 'user@example.com']);
```

## API Reference

### Event::on()

Register a permanent listener:

```php
Event::on('order.placed', function ($order) {
    // Process order
});
```

### Event::once()

Register a listener that fires only once:

```php
Event::once('app.booted', function () {
    // Run initialization logic
});

// Second emit won't trigger
Event::emit('app.booted');  // triggers
Event::emit('app.booted');  // no-op
```

### Event::emit()

Emit an event with optional payload:

```php
Event::emit('event.name', $payload);
```

Returns `true` if all listeners completed, `false` if propagation was cancelled.

### Event::off()

Remove all listeners for an event:

```php
Event::off('user.registered');
```

### Event::flush()

Remove all listeners for all events:

```php
Event::flush();
```

### Event::hasListeners()

Check if an event has registered listeners:

```php
if (Event::hasListeners('user.registered')) {
    // Has listeners
}
```

### Event::currentEvent()

Get the name of the currently firing event (useful in wildcard listeners):

```php
Event::on('users.*', function ($payload) {
    $event = Event::currentEvent();  // 'users.created', 'users.updated', etc.
    Log::info("$event fired");
});
```

## Event Cancellation

A listener can cancel further propagation by returning `false`:

```php
Event::on('user.deleting', function ($user) {
    if ($user['is_protected']) {
        return false;  // Cancels the delete, other listeners won't fire
    }
});
```

When cancelled, `Event::emit()` returns `false`.

## Wildcard Listeners

Listen for multiple events using `*` wildcard:

```php
Event::on('users.*', function ($payload) {
    // Catches: users.created, users.updated, users.deleted, etc.
});
```

## Model Lifecycle Events

Models automatically fire events during CRUD operations:

| Event Name | When | Can Cancel |
|------------|------|------------|
| `{table}.creating` | Before insert | Yes (return `false`) |
| `{table}.created` | After insert | No |
| `{table}.saving` | Before save | Yes (return `false`) |
| `{table}.saved` | After save | No |
| `{table}.deleting` | Before delete | Yes (return `false`) |
| `{table}.deleted` | After delete | No |

```php
// The `creating`/`saving`/`deleting` events receive the Model instance
Event::on('users.creating', function ($model) {
    $model->token_version = 1;
});

// The `created`/`saved`/`deleted` events receive no arguments
Event::on('users.created', function () {
    Log::info('User was created');
});

// Block deletion of protected users
Event::on('users.deleting', function ($model) {
    if ($model->role === 'superadmin') {
        return false;  // Prevent deletion
    }
});
```

## Custom Events

Create dedicated event classes for better organization:

```bash
php siro make:event UserCreated
```

```php
namespace App\Events;

use Siro\Core\Event;

final class UserCreatedEvent
{
    public static function dispatch(mixed $payload = null): void
    {
        Event::emit('user_created_event', $payload);
    }

    public static function listen(callable $callback): void
    {
        Event::on('user_created_event', $callback);
    }
}

// Usage
UserCreatedEvent::dispatch(['id' => 1, 'email' => 'user@example.com']);

// Register listener
UserCreatedEvent::listen(function ($payload) {
    // Handle event
});
```

## Best Practices

- Use events to decouple side effects (logging, notifications, analytics) from core logic.
- Keep event names namespaced: `{context}.{action}` (e.g. `order.placed`, `payment.failed`).
- Use wildcard listeners sparingly — prefer specific event names.
- Return `false` from lifecycle `*ing` events to conditionally prevent the operation.
- Always flush events between tests: `Event::flush()`.
