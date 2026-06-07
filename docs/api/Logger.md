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
Logger::request('GET /api/products — 200', ['duration_ms' => 12.3]);
Logger::slowRequest('Query took 450ms', ['sql' => $sql, 'duration' => 450]);
Logger::warning('Slow query detected', ['sql' => $sql, 'duration' => 500]);
Logger::error('Payment failed', ['order_id' => 100, 'reason' => 'insufficient_funds']);
Logger::security('Failed login attempt', ['ip' => $ip, 'email' => $email]);
Logger::trace('Trace captured', ['trace_id' => $traceId]);
```

---

## Log Sanitization

Sensitive data is automatically redacted from logs:

```php
// These values are REDACTED in log output
Logger::debug('Login', [
    'password' => 'secret123',           // → [REDACTED]
    'token' => 'eyJ...',                 // → [REDACTED]  
    'authorization' => 'Bearer eyJ...',  // → [REDACTED]
    'credit_card' => '4111-1111-1111',   // → [REDACTED]
    'x-api-key' => 'abc123',            // → [REDACTED]
    'cookie' => 'session=abc',          // → [REDACTED]
]);
```

Sanitized fields: `authorization`, `cookie`, `x-api-key`, `x-csrf-token`, `session-id`, `password`, `token`, `otp`, `secret`, `credit_card`, `credit-card`, `card_number`, `cvv`, `pin`, `ssn`, `passport`.

---

## Context Logging

Context data is passed as the second argument to any log method:

```php
// Log entries with context
Logger::debug('Order created', ['order_id' => 100, 'user_id' => 42]);
// Output: { "message": "Order created", "context": { "order_id": 100, "user_id": 42 } }
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
| `debug(string $message, array $context = [])` | Debug level |
| `request(string $message, array $context = [])` | Request logging |
| `slowRequest(string $message, array $context = [])` | Slow request logging |
| `warning(string $message, array $context = [])` | Warning level |
| `error(string $message, array $context = [])` | Error level |
| `security(string $message, array $context = [])` | Security events (SIEM-ready) |
| `trace(string $message, array $context = [])` | Request trace capture |
