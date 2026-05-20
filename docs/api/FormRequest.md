# FormRequest API Reference

## Overview

FormRequests encapsulate validation logic in a single class — keeping controllers clean and rules reusable.

```php
use Siro\Core\FormRequest;
```

---

## Generate

```bash
php siro make:request StoreProductRequest
```

---

## Define

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
            'description' => 'string|max:10000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'price.min' => 'Price cannot be negative',
        ];
    }

    public function authorize(): bool
    {
        // Check if user can create products
        $user = $this->get('_user');
        return $user !== null && $user['role'] === 'admin';
    }
}
```

---

## Use in Controller

```php
public function store(StoreProductRequest $request): Response
{
    // Validation already passed
    // authorize() already checked

    $data = $request->validated();  // Only validated fields
    // ['name' => 'Laptop', 'price' => 999, 'category_id' => 1]

    $product = $this->service->create($data);
    return $this->created($product, 'Product created');
}
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `rules()` | Define validation rules |
| `messages()` | Custom error messages |
| `authorize()` | Authorization check (return bool) |
| `validated()` | Get validated data only |
| `errors()` | Get validation errors |
| `fails()` | Check if validation failed |
| `validate()` | Run validation manually |
| `get(string $key, mixed $default)` | Get a validated field |
| `all()` | Get all validated data |
