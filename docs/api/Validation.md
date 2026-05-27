---
title: V al id at io n
description: SiroPHP V al id at io n reference
sidebar_position: 34
sidebar_label: V al id at io n
---

# Validation API Reference

## Overview

Siro provides a fluent validation system with 15+ built-in rules, custom rule support, and FormRequest pattern.

```php
$validated = $request->validate([
    'email' => 'required|email|max:255|unique:users,email',
    'password' => 'required|min:8|max:255|confirmed',
]);
```

---

## Available Rules

### String Rules

| Rule | Description |
|------|-------------|
| `min:N` | Minimum length |
| `max:N` | Maximum length |
| `min:N` | Minimum value/length |
| `max:N` | Maximum value/length |
| `between:N,M` | Between N and M |
| `alpha` | Alphabetic characters only |
| `alpha_num` | Alpha-numeric only |
| `alpha_dash` | Alpha-numeric, dashes, underscores |

### Type Rules

| Rule | Description |
|------|-------------|
| `required` | Field is required |
| `string` | Must be string |
| `numeric` | Must be numeric |
| `integer` | Must be integer |
| `bool` | Must be boolean |
| `array` | Must be array |
| `file` | Must be uploaded file |
| `image` | Must be image (jpeg, png, gif, webp) |

### Data Rules

| Rule | Description |
|------|-------------|
| `email` | Valid email address |
| `url` | Valid URL |
| `ip` | Valid IP address |
| `date` | Valid date |
| `datetime` | Valid datetime |
| `json` | Valid JSON string |
| `regex:/pattern/` | Match regex pattern |

### Database Rules

| Rule | Description |
|------|-------------|
| `exists:table,column` | Value exists in database |
| `unique:table,column` | Value is unique in table |

### Comparison Rules

| Rule | Description |
|------|-------------|
| `confirmed` | Field must have `_confirmation` match |
| `in:a,b,c` | Must be one of the values |
| `not_in:a,b,c` | Must not be one of the values |
| `same:field` | Must match another field |
| `different:field` | Must differ from another field |

### Size Rules

| Rule | Description |
|------|-------------|
| `size:N` | Exact length/value |
| `min:N` | Minimum length/value |
| `max:N` | Maximum length/value |

### File Rules

| Rule | Description |
|------|-------------|
| `file` | Must be uploaded file |
| `image` | Must be image type |
| `mimes:png,jpg` | Allowed MIME types |
| `min:N` | Min file size in KB |
| `max:N` | Max file size in KB |

---

## Usage Examples

### Basic Validation

```php
$validated = $request->validate([
    'name' => 'required|string|min:3|max:120',
    'email' => 'required|email|max:255|unique:users,email',
    'password' => 'required|min:8|max:255|confirmed',
]);
```

### Nested Validation

```php
$validated = $request->validate([
    'user.name' => 'required|min:3',
    'user.email' => 'required|email',
    'items.*.product_id' => 'required|exists:products,id',
    'items.*.quantity' => 'required|integer|min:1',
]);
```

### Custom Error Messages

```php
$messages = [
    'email.required' => 'We need your email address',
    'email.email' => 'That does not look like an email',
    'password.min' => 'Password must be at least 8 characters',
];

$validated = $request->validate($rules, $messages);
```

---

## FormRequest Pattern

### Create FormRequest

```bash
php siro make:request StoreProductRequest
```

### Define Rules

```php
<?php

declare(strict_types=1);

namespace App\Requests;

use Siro\Core\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'price.min' => 'Price cannot be negative',
        ];
    }
}
```

### Use in Controller

```php
public function store(StoreProductRequest $request): Response
{
    // Already validated
    $data = $request->validated();

    $product = $this->service->create($data);
    return $this->created($data, 'Product created');
}
```

---

## Custom Rules

### Define Custom Rule

```php
use Siro\Core\Validator;

Validator::extend('phone', function (string $field, mixed $value, array $params): bool {
    return preg_match('/^\+?[\d\s\-\(\)]{7,15}$/', (string) $value) === 1;
}, 'The :field must be a valid phone number.');

// Usage
'phone' => 'required|phone'
```

### Custom Rule with Parameters

```php
Validator::extend('min_age', function (string $field, mixed $value, array $params): bool {
    $minAge = (int) ($params[0] ?? 18);
    $birthDate = new \DateTime((string) $value);
    $age = $birthDate->diff(new \DateTime())->y;
    return $age >= $minAge;
}, 'You must be at least :param0 years old.');

// Usage
'birth_date' => 'required|date|min_age:18'
```

---

## Error Response

On validation failure, a 422 response is returned:

```json
{
    "success": false,
    "message": "Validation failed",
    "data": null,
    "errors": {
        "email": ["Email has already been taken"],
        "password": ["Password must be at least 8 characters"]
    }
}
```
