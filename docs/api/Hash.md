---
title: H as h
description: SiroPHP H as h reference
sidebar_position: 10
sidebar_label: H as h
---

# Hash API Reference

## Overview

Secure password hashing using bcrypt with configurable cost factor.

```php
use Siro\Core\Hash;
```

---

## Basic Usage

```php
// Hash a password (default cost: 12)
$hash = Hash::make('user-password');

// Verify a password against hash
if (Hash::check('user-password', $hash)) {
    // Password is correct
}

// Check if hash needs rehashing (cost increased)
if (Hash::needsRehash($hash)) {
    $newHash = Hash::make('user-password', ['cost' => 14]);
}

// Get hash info
$info = Hash::info($hash);
// ['algo' => 'bcrypt', 'algoName' => 'bcrypt', 'options' => ['cost' => 12]]
```

---

## Cost Factor

```php
// Default cost (recommended balance of security and speed)
$hash = Hash::make($password);
// Uses cost = 12 (~250ms on modern hardware)

// Higher cost (more secure, slower)
$hash = Hash::make($password, ['cost' => 14]);
// Uses cost = 14 (~1s on modern hardware)

// Lower cost (faster, less secure — dev only)
$hash = Hash::make($password, ['cost' => 8]);
```

Adjust cost based on your hardware. Aim for ~250-500ms per hash.

---

## In Practice

```php
// UserService.php
private static function hashPassword(string $password): string
{
    return Hash::make($password, ['cost' => 12]);
}

// Login
public function login(string $email, string $password): ?array
{
    $user = $this->repo->findByEmail($email);
    if ($user === null || !Hash::check($password, $user['password'])) {
        return null;
    }
    return $user;
}
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `make(string $value, array $options)` | Hash value with bcrypt (cost defaults to 12) |
| `check(string $value, string $hash)` | Verify value against hash |
| `needsRehash(string $hash, array $options)` | Check if hash uses current cost |
| `info(string $hash)` | Get algorithm and cost info |
