---
title: H tt p
description: SiroPHP H tt p reference
sidebar_position: 12
sidebar_label: H tt p
---

# Http API Reference

## Overview

Zero-dependency HTTP client for making outbound requests (GET, POST, PUT, PATCH, DELETE) via cURL.

```php
use Siro\Core\Http;
```

---

## Quick Start

```php
// GET
$response = Http::get('https://api.github.com/repos/SiroSoft/SiroPHP');

// POST with JSON
$response = Http::post('https://api.example.com/orders', [
    'json' => ['product_id' => 1, 'quantity' => 2],
]);

// Status
$response->status();       // 200
$response->ok();           // true (status 2xx)
$response->failed();       // false

// Body
$response->body();         // Raw string
$response->json();         // Parsed array

// Headers
$response->header('content-type');  // 'application/json'
$response->headers();               // all headers
```

---

## Methods

```php
// GET
$response = Http::get('https://api.example.com/users', [
    'query' => ['page' => 1, 'per_page' => 20],
]);

// POST
$response = Http::post('https://api.example.com/orders', [
    'json' => ['product_id' => 1, 'quantity' => 2],
]);

// PUT
$response = Http::put('https://api.example.com/orders/1', [
    'json' => ['status' => 'shipped'],
]);

// PATCH
$response = Http::patch('https://api.example.com/orders/1', [
    'json' => ['status' => 'shipped'],
]);

// DELETE
$response = Http::delete('https://api.example.com/orders/1');
```

## Configuration

```php
// Timeout (default: 30s)
Http::timeout(10);

// SSL verification
Http::verify(true);   // Default
Http::verify(false);  // Skip SSL (dev only)

// Default headers for all requests
Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'User-Agent' => 'Siro-App/1.0',
]);
```

---

## Response Object

```php
$response = Http::get('https://api.github.com/repos/SiroSoft/SiroPHP');

$response->status();        // int: 200
$response->body();          // string: raw response
$response->json();          // array|null: parsed JSON
$response->ok();            // bool: status 2xx
$response->failed();        // bool: status 4xx or 5xx
$response->header('key');   // string|null: single header
$response->headers();       // array: all headers
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `get(string $url, array $options)` | GET request |
| `post(string $url, array $options)` | POST request |
| `put(string $url, array $options)` | PUT request |
| `patch(string $url, array $options)` | PATCH request |
| `delete(string $url, array $options)` | DELETE request |
| `timeout(int $seconds)` | Set timeout |
| `verify(bool $verify)` | Toggle SSL verification |
| `withHeaders(array $headers)` | Set default headers |
| `status()` | Response status code |
| `body()` | Response body string |
| `json()` | Response parsed as JSON |
| `ok()` | Check if 2xx |
| `failed()` | Check if 4xx or 5xx |
| `header(string $key)` | Get response header |
| `headers()` | Get all response headers |
