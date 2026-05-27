---
title: S es si on
description: SiroPHP S es si on reference
sidebar_position: 27
sidebar_label: S es si on
---

# Session API Reference

## Overview

Siro provides file and Redis-based session management with automatic cleanup and CSRF protection integration.

```php
use Siro\Core\Session;
```

---

## Configuration

```env
SESSION_DRIVER=file           # file, redis
SESSION_LIFETIME=120          # minutes
SESSION_FILES=storage/sessions
```

---

## Basic Usage

```php
// Start session
$session = Session::instance();

// Set value
$session->set('user_id', 42);
$session->set('cart', ['product_id' => 1, 'quantity' => 2]);

// Get value
$userId = $session->get('user_id', 0);
$cart = $session->get('cart', []);

// Check if key exists
if ($session->has('user_id')) { ... }

// Remove value
$session->remove('user_id');

// Clear all
$session->clear();
```

---

## Flash Data

Data that persists only for the next request:

```php
// Set flash
$session->flash('status', 'Profile updated');

// Get flash (auto-deleted after retrieval)
$status = $session->get('status');

// Keep flash for another request
$session->reflash();

// Keep specific flash values
$session->keep(['status', 'message']);
```

---

## Session Regeneration

```php
// Regenerate session ID (call after login to prevent fixation)
$session->regenerate();

// Regenerate and delete old session
$session->regenerate(true);
```

---

## CSRF Token

```php
// Generate CSRF token
$token = $session->token();

// Verify CSRF token
$isValid = $session->validateCsrf($token);

// Regenerate CSRF token
$session->regenerateToken();
```

---

## Session ID

```php
// Get current session ID
$id = $session->getId();

// Set custom session ID
$session->setId('custom-session-id');

// Invalidate session
$session->invalidate();
```

---

## Security

```php
// After successful login — ALWAYS regenerate
Session::instance()->regenerate();

// On logout — invalidate completely
Session::instance()->invalidate();
```

Session files are stored with restrictive permissions. Redis sessions auto-expire via TTL.

---

## Available Methods

| Method | Description |
|--------|-------------|
| `instance()` | Get singleton session instance |
| `start()` | Start session |
| `getId()` | Get session ID |
| `setId(string $id)` | Set session ID |
| `get(string $key, mixed $default)` | Get session value |
| `set(string $key, mixed $value)` | Set session value |
| `has(string $key)` | Check if key exists |
| `remove(string $key)` | Remove session value |
| `clear()` | Clear all session data |
| `regenerate(bool $deleteOld)` | Regenerate session ID |
| `invalidate()` | Clear all data and regenerate |
| `flash(string $key, mixed $value)` | Set flash data |
| `reflash()` | Keep all flash data |
| `keep(array $keys)` | Keep specific flash keys |
| `token()` | Get CSRF token |
| `regenerateToken()` | Regenerate CSRF token |
| `validateCsrf(string $token)` | Validate CSRF token |
