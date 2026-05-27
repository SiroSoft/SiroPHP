---
title: L an g
description: SiroPHP L an g reference
sidebar_position: 13
sidebar_label: L an g
---

# Lang API Reference

## Overview

Internationalization (i18n) system supporting multiple locales with fallback.

```php
use Siro\Core\Lang;
```

---

## Configuration

```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
```

## Language Files

```
resources/lang/
├── en/
│   ├── messages.php
│   └── validation.php
└── vi/
    ├── messages.php
    └── validation.php
```

```php
// resources/lang/en/messages.php
return [
    'welcome' => 'Welcome to Siro',
    'greeting' => 'Hello, :name!',
    'order.created' => 'Order #:id created successfully',
];
```

---

## Usage

```php
// Simple string
echo Lang::get('messages.welcome');
// "Welcome to Siro"

// With parameters
echo Lang::get('messages.greeting', ['name' => 'John']);
// "Hello, John!"

// Nested keys
echo Lang::get('messages.order.created', ['id' => 42]);
// "Order #42 created successfully"
```

## Locale

```php
// Get current locale
$locale = Lang::locale();         // 'en'

// Set locale (per-request)
Lang::setLocale('vi');

// Fallback
Lang::setFallbackLocale('en');    // Default

// Check if key exists
if (Lang::has('messages.welcome')) { ... }

// Count available messages
$count = Lang::count();           // number of loaded strings
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `boot(string $basePath)` | Load language files |
| `basePath(string $path)` | Set base path for lang files |
| `locale()` | Get current locale |
| `setLocale(string $locale)` | Set locale |
| `setFallbackLocale(string $locale)` | Set fallback locale |
| `get(string $key, array $replace)` | Get translated string |
| `has(string $key)` | Check if key exists |
| `count()` | Count loaded strings |
| `plural(string $key, int $count)` | Get plural form |
