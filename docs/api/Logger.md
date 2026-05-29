---
title: L og ge r
description: SiroPHP L og ge r reference
sidebar_position: 14
sidebar_label: L og ge r
---

# Logger API Reference

## Overview

Siro provides structured logging with multiple channels, log level filtering, automatic sanitization, and file rotation.

```php
use Siro\Core\Logger;
```

---

## Configuration

```env
LOG_LEVEL=debug              # debug, info, notice, warning, error, critical, alert, emergency
LOG_RETENTION_DAYS=30       # Auto-clean logs older than N days
LOG_MAX_SIZE_MB=1024        # Max total log storage
```

---

## Log Levels

```php
Logger::debug('Query executed', ['sql' => $sql, 'time' => '2.3ms']);
Logger::info('User registered', ['user_id' => 42]);
Logger::notice('Rate limit approaching', ['remaining' => 5]);
Logger::warning('Slow query detected', ['sql' => $sql, 'duration' => 500]);
Logger::error('Payment failed', ['order_id' => 100, 'reason' => 'insufficient_funds']);
Logger::critical('Database connection lost');
Logger::alert('Disk space critical');
Logger::emergency('System is down');
```

---

## Log Channels

```php
// Default channel
Logger::info('Request completed');

// Request log
Logger::channel('request')->info('GET /api/products — 200');

// Slow query log
Logger::channel('slow')->warning('Query took 450ms', ['sql' => $sql]);

// Security log (SIEM-ready)
Logger::channel('security')->warning('Failed login attempt', [
    'ip' => $ip,
    'email' => $email,
    'attempts' => 3,
]);

// Error log
Logger::channel('error')->error('Unhandled exception', [
    'exception' => get_class($e),
    'message' => $e->getMessage(),
]);

// Debug log
Logger::channel('debug')->debug('Variable dump', $data);

// Trace log (per-request)
Logger::channel('trace')->info('Trace captured', ['trace_id' => $traceId]);
```

---

## Log Sanitization

Sensitive data is automatically redacted from logs:

```php
// These values are REDACTED in log output
Logger::info('Login', [
    'password' => 'secret123',           // → [REDACTED]
    'token' => 'eyJ...',                 // → [REDACTED]  
    'authorization' => 'Bearer eyJ...',  // → [REDACTED]
    'credit_card' => '4111-1111-1111',   // → [REDACTED]
    'x-api-key' => 'abc123',            // → [REDACTED]
    'cookie' => 'session=abc',          // → [REDACTED]
]);
```

Sanitized fields: `authorization`, `cookie`, `x-api-key`, `password`, `passwd`, `token`, `secret`, `credit_card`, `cc_number`, `jwt`, `bearer`, `refresh_token`, `api_key`, `private_key`.

---

## Context Logging

```php
// Global context (included in every log entry)
Logger::setContext([
    'trace_id' => $traceId,
    'user_id' => $userId,
    'ip' => $request->ip(),
]);

// Log entries automatically include context
Logger::info('Order created', ['order_id' => 100]);
// Output: { "message": "Order created", "context": { "order_id": 100 }, "global": { "trace_id": "...", "user_id": 42 } }
```

---

## Log Storage

```
storage/logs/
├── daily/          # Rotated daily (archive)
│   ├── 2026-05-19.log
│   └── 2026-05-20.log
├── main/           # Current logs
│   ├── request.log
│   ├── slow.log
│   └── error.log
└── traces/         # Request traces
    └── siro_a1b2c3d4.json
```

### Auto-Cleanup

Logs older than `LOG_RETENTION_DAYS` are automatically deleted. Max total size is limited by `LOG_MAX_SIZE_MB`.

---

## Log Format

```
[2026-05-20 10:30:00] {channel}.{level}: {message} {context_json}
```

Example:

```
[2026-05-20 10:30:00] request.info: GET /api/products — 200 {"duration_ms":12.3,"trace_id":"siro_a1b2c3"}
[2026-05-20 10:30:01] slow.warning: Query took 450ms {"sql":"SELECT * FROM orders WHERE...","trace_id":"siro_a1b2c3"}
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `debug(string $message, array $context)` | Debug level |
| `info(string $message, array $context)` | Info level |
| `notice(string $message, array $context)` | Notice level |
| `warning(string $message, array $context)` | Warning level |
| `error(string $message, array $context)` | Error level |
| `critical(string $message, array $context)` | Critical level |
| `alert(string $message, array $context)` | Alert level |
| `emergency(string $message, array $context)` | Emergency level |
| `channel(string $name)` | Get/create log channel |
| `setContext(array $context)` | Set global context |
| `sanitize(array $data)` | Sanitize sensitive data |
| `log(string $level, string $message, array $context)` | Log at arbitrary level |
