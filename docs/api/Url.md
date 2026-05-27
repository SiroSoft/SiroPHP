---
title: U rl
description: SiroPHP U rl reference
sidebar_position: 33
sidebar_label: U rl
---

# URL API Reference

## Overview

Generate and validate signed URLs — useful for password reset links, email verification, or any expiring one-time URLs.

```php
use Siro\Core\URL;
```

---

## Signed URLs

```php
// Generate signed URL (valid for 1 hour)
$link = URL::signed('/api/auth/reset-password', [
    'email' => 'user@test.com',
], 3600);
// https://example.com/api/auth/reset-password?payload=...&signature=...

// Validate incoming request
$request = [
    'payload' => $_GET['payload'],
    'signature' => $_GET['signature'],
];

$data = URL::validate($request['payload'], $request['signature']);
if ($data === null) {
    // Invalid or expired URL
}
// $data = ['route' => '/api/auth/reset-password', 'params' => ['email' => 'user@test.com']]
```

---

## Validate from Request

```php
// In controller
$data = URL::validateRequest();

if ($data === null) {
    return Response::error('Invalid or expired link', 400);
}

$email = $data['params']['email'] ?? '';
// Process password reset
```

---

## Use Case: Password Reset

```php
// Generate link
$token = bin2hex(random_bytes(32));
User::where('email', $email)->update(['reset_token' => hash('sha256', $token)]);

$link = URL::signed('/api/auth/reset-password', [
    'email' => $email,
    'token' => $token,
], 3600);

// Email the link to user
Mail::to($email)->subject('Reset Your Password')->html($link);

// Controller validates
public function resetPassword(Request $request): Response
{
    $data = URL::validateRequest();
    if ($data === null) {
        return Response::error('Invalid or expired reset link', 400);
    }
    // Process reset...
}
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `signed(string $route, array $params, ?int $expires)` | Generate signed URL |
| `validate(string $payload, string $signature, bool $throw)` | Validate signature and expiry |
| `validateRequest(bool $throw)` | Validate from current request |
