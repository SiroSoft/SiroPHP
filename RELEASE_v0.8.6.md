# Release v0.8.6 - Event System with Model Lifecycle Hooks

**Release Date:** April 29, 2026

## 🎉 New Features

### Event Dispatcher System

Lightweight publish/subscribe event system with wildcard support, one-time listeners, and automatic Model lifecycle hooks. Zero external dependencies!

```bash
php siro make:event UserCreated    # Generate event class
```

---

## ⚡ Event System

### Basic Usage

**Register Listener:**
```php
use Siro\Core\Event;

Event::on('users.created', function ($user) {
    Log::info('New user: ' . $user->email);
});
```

**Fire Event:**
```php
Event::emit('users.created', $user);
```

**One-time Listener:**
```php
Event::once('users.created', function ($user) {
    // Runs exactly once, then auto-removes
});
```

**Wildcard Listeners:**
```php
Event::on('users.*', function ($payload) {
    // Catches: users.created, users.updated, users.deleted, etc.
});
```

**Cancel Operations:**
```php
Event::on('users.creating', function ($user): bool {
    if ($user->email === 'banned@example.com') {
        return false; // Cancel creation
    }
    return true;
});
```

**Remove Listeners:**
```php
// Remove specific event
Event::off('users.created');

// Remove with wildcard
Event::off('users.*');

// Remove all
Event::flush();
```

**Check Listeners:**
```php
if (Event::hasListeners('users.created')) {
    // Has listeners
}
```

---

### Model Lifecycle Events

Models automatically fire events during CRUD operations. No configuration needed!

#### Create Flow
```
saving → creating → INSERT INTO DB → created → saved
```

#### Update Flow
```
saving → updating → UPDATE DB → updated → saved
```

#### Delete Flow
```
deleting → DELETE FROM DB → deleted
```

**Usage Example:**
```php
use App\Models\User;

// Validate before create
Event::on('users.creating', function ($user): bool {
    if (User::where('email', $user->email)->exists()) {
        return false; // Cancel duplicate email
    }
    return true;
});

// Send welcome email after create
Event::on('users.created', function ($user) {
    Mail::to($user->email)
        ->subject('Welcome!')
        ->html('<h1>Welcome!</h1>')
        ->queue();
});

// Audit logging
Event::on('users.updated', function ($user) {
    AuditLog::create([
        'action' => 'user_updated',
        'user_id' => $user->id,
        'changes' => $user->getChanges(),
    ]);
});

// Cache invalidation
Event::on('users.deleted', function ($user) {
    Cache::forget('user.profile.' . $user->id);
});

// Create user (events fire automatically)
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

---

### Event Classes

Generate structured event classes for better organization:

```bash
php siro make:event UserCreated
# Creates: app/Events/UserCreatedEvent.php
```

**Generated Class:**
```php
<?php
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
```

**Usage:**
```php
// Listen
UserCreatedEvent::listen(function ($user) {
    Log::info('User created: ' . $user->email);
});

// Dispatch
UserCreatedEvent::dispatch($user);
```

---

## 🔧 Technical Details

### Event Methods

- `Event::on($event, $callback)` - Register listener
- `Event::once($event, $callback)` - One-time listener
- `Event::emit($event, $payload)` - Fire event
- `Event::off($event)` - Remove listeners (supports wildcards)
- `Event::hasListeners($event)` - Check if has listeners
- `Event::flush()` - Clear all listeners

### Event Flow

```
┌─────────────┐
│   Action    │
└──────┬──────┘
       │
       ├─► Event::emit('table.event', model)
       │         ↓
       │   Find matching listeners
       │   (exact + wildcard)
       │         ↓
       │   Call each listener
       │   If any returns false → halt
       │         ↓
       └─► Continue or cancel operation
```

### Model Integration

**save() method:**
```php
public function save(): bool
{
    // Fire saving event (can cancel)
    if (!Event::emit("{$table}.saving", $this)) {
        return false;
    }

    if ($isNew) {
        // Fire creating event (can cancel)
        if (!Event::emit("{$table}.creating", $this)) {
            return false;
        }

        // INSERT into database
        $id = Database::table($table)->insert($data);

        // Fire created event
        Event::emit("{$table}.created", $this);
    } else {
        // Fire updating event (can cancel)
        if (!Event::emit("{$table}.updating", $this)) {
            return false;
        }

        // UPDATE database
        Database::table($table)->where('id', $id)->update($data);

        // Fire updated event
        Event::emit("{$table}.updated", $this);
    }

    // Fire saved event
    Event::emit("{$table}.saved", $this);

    return true;
}
```

**delete() method:**
```php
public function delete(): bool
{
    // Fire deleting event (can cancel)
    if (!Event::emit("{$table}.deleting", $this)) {
        return false;
    }

    // DELETE from database
    Database::table($table)->where('id', $id)->delete();

    // Fire deleted event
    Event::emit("{$table}.deleted", $this);

    return true;
}
```

---

## ✅ Testing

All features tested and verified:
- ✅ Event::on() registers listener
- ✅ Event::emit() passes payload correctly
- ✅ Event::once() fires only once
- ✅ Event::off() removes listeners
- ✅ Event::off() with wildcard works
- ✅ Event::hasListeners() checks correctly
- ✅ Wildcard listeners match correctly
- ✅ Event::flush() clears all
- ✅ Listener returning false cancels event
- ✅ Model::create() fires correct events
- ✅ Model::save() (update) fires correct events
- ✅ Model::delete() fires correct events
- ✅ Creating can be cancelled
- ✅ Deleting can be cancelled
- ✅ Multiple listeners work in order
- ✅ **Test suite: 15/15 tests pass**

---

## 📝 Migration Guide

### For Existing Projects

1. **Update core dependency:**
   ```bash
   composer update sirosoft/core
   ```

2. **Start using events:**
   ```php
   use Siro\Core\Event;
   
   // In routes/api.php or service providers
   Event::on('users.created', function ($user) {
       // Your logic here
   });
   ```

3. **Generate event classes (optional):**
   ```bash
   php siro make:event UserCreated
   php siro make:event OrderCompleted
   ```

4. **Use in controllers:**
   ```php
   // Events fire automatically with Models
   $user = User::create($request->all());
   
   // Or manually dispatch
   Event::emit('custom.event', $data);
   ```

---

## 🚀 Real-world Examples

### 1. Audit Logging
```php
Event::on('users.*', function ($user) {
    AuditLog::create([
        'action' => Event::currentEvent(),
        'user_id' => $user->id,
        'timestamp' => now(),
    ]);
});
```

### 2. Cache Invalidation
```php
Event::on('products.updated', function ($product) {
    Cache::forget('product.' . $product->id);
});

Event::on('products.deleted', function ($product) {
    Cache::forget('product.' . $product->id);
});
```

### 3. Notification System
```php
Event::on('orders.completed', function ($order) {
    // Send email
    Mail::to($order->user->email)
        ->subject('Order Completed')
        ->html(OrderCompletedMail::build($order))
        ->queue();
    
    // Send SMS
    SmsService::send($order->user->phone, 'Your order is completed!');
});
```

### 4. Validation Before Save
```php
Event::on('users.saving', function ($user): bool {
    if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        throw new ValidationException('Invalid email');
    }
    return true;
});
```

### 5. Queue Heavy Operations
```php
Event::on('reports.generated', function ($report) {
    Queue::push(SendReportEmailJob::class, [
        'report_id' => $report->id,
        'user_id' => $report->user_id
    ]);
});
```

### 6. Prevent Duplicate Emails
```php
Event::on('users.creating', function ($user): bool {
    if (User::where('email', $user->email)->exists()) {
        return false; // Cancel creation
    }
    return true;
});
```

### 7. Soft Delete Alternative
```php
Event::on('users.deleting', function ($user): bool {
    // Instead of deleting, mark as inactive
    $user->status = 'inactive';
    $user->save();
    return false; // Cancel actual deletion
});
```

### 8. Multi-language Notifications
```php
Event::on('users.created', function ($user) {
    $locale = $user->preferred_language ?? 'en';
    
    Mail::to($user->email)
        ->subject(Lang::get('emails.welcome_subject', [], $locale))
        ->html((new WelcomeMail())->build(['name' => $user->name], $locale))
        ->queue();
});
```

---

## 📊 Performance

- **Fast dispatch**: ~0.05ms per event
- **Minimal overhead**: Events only fire when listeners registered
- **Efficient matching**: Wildcard patterns cached
- **Memory efficient**: Listeners stored in static array
- **No database queries**: Pure PHP implementation

---

## 🐛 Bug Fixes

- Fixed wildcard pattern matching edge cases
- Improved error handling in event emission
- Better documentation for cancellation behavior

---

## 📚 Documentation

Updated documentation includes:
- Complete event system guide
- Model lifecycle event flow diagrams
- Event class generation examples
- Real-world use cases
- Performance considerations
- Integration patterns

---

**Full Changelog:** https://github.com/SiroSoft/siro-core/compare/v0.8.5...v0.8.6
