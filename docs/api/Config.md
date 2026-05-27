---
title: C on fi g
description: SiroPHP C on fi g reference
sidebar_position: 3
sidebar_label: C on fi g
---

# Config API Reference

## Overview

Siro's configuration system loads PHP files from `config/` directory and supports HMAC-signed caching for production.

```php
use Siro\Core\Config;
```

---

## Configuration Files

```
config/
├── app.php           # Application settings
├── database.php      # Database connections
├── jwt.php           # JWT configuration
├── cache.php         # Cache settings
├── cors.php          # CORS configuration
├── mail.php          # Mail driver settings
└── logging.php       # Logging configuration
```

Each file returns an array:

```php
// config/app.php
return [
    'name' => env('APP_NAME', 'Siro API'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
];
```

---

## Accessing Config

```php
// Dot notation
$name = Config::get('app.name');           // 'Siro API'
$debug = Config::get('app.debug', false);  // with default
$db = Config::get('database.connections.sqlite.database');

// Check if exists
if (Config::has('jwt.ttl')) { ... }

// Get all
$all = Config::all();

// Set at runtime
Config::set('app.debug', true);
```

---

## Config Caching (Production)

```bash
php siro config:cache
# → Config cached to storage/framework/config.php (HMAC-signed)

php siro config:clear
# → Clears cached config
```

In production, cached config is loaded in ~0.01ms instead of scanning the directory.

---

## Available Methods

| Method | Description |
|--------|-------------|
| `load(string $configPath)` | Load config files from directory |
| `get(string $key, mixed $default)` | Get config value (dot notation) |
| `set(string $key, mixed $value)` | Set config value at runtime |
| `has(string $key)` | Check if config key exists |
| `all()` | Get all config values |
| `cache()` | Cache config to file (HMAC-signed) |
| `clearCache()` | Delete cached config |
| `reset()` | Reset all loaded config |
| `isLoaded()` | Check if config is loaded |
