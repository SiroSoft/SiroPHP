# Resource API Reference

## Overview

Resources transform your models/arrays into consistent JSON API responses. One resource = one shape of data.

```php
use App\Resources\UserResource;
```

---

## Quick Start

### Generate

```bash
php siro make:resource UserResource
```

### Define

```php
final class UserResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'],
            'name' => $this->data['name'],
            'email' => $this->data['email'],
            'created_at' => $this->data['created_at'],
        ];
    }
}
```

### Use

```php
// Single resource
return UserResource::make($userData);

// Collection
return UserResource::collection($usersList);

// Collection with field filter
return UserResource::collectionOf($usersList, ['id', 'name']);
```

---

## Why Resources?

**Before** (raw data leaks everything):

```php
return $userData;
// Returns: id, name, email, password, token_version, verification_token, ...
```

**After** (Resource controls the shape):

```php
return UserResource::make($userData);
// Returns: id, name, email, created_at
```

Never accidentally expose `password`, `token_version`, or internal fields again.

---

## Hiding Sensitive Fields

```php
public function toArray(): array
{
    // Only return what the API consumer needs
    return [
        'id' => $this->data['id'],
        'name' => htmlspecialchars($this->data['name'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'email' => $this->data['email'],
    ];
    // Everything else is excluded from response
}
```

---

## Relationships

```php
final class OrderResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'],
            'total' => (float) ($this->data['total'] ?? 0),
            'status' => $this->data['status'] ?? 'pending',
            'items' => $this->data['items'] ?? [],
            // Nested resource
            'user' => UserResource::make($this->data['user'] ?? []),
        ];
    }
}
```

---

## XSS Protection

Always escape user-generated strings:

```php
public function toArray(): array
{
    return [
        'name' => is_string($this->data['name'] ?? null)
            ? htmlspecialchars($this->data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')
            : ($this->data['name'] ?? null),
    ];
}
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `make(array\|Model $item, ?array $fields)` | Transform single item |
| `collection(array $items)` | Transform list of items |
| `collectionOf(array $items, array $fields)` | Transform list with field filter |
| `toArray()` | Define the output shape (override this) |
