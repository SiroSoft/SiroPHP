---
title: O bs er ve rs
description: SiroPHP O bs er ve rs reference
sidebar_position: 19
sidebar_label: O bs er ve rs
---

# Observers API Reference

## Overview

Observers hook into model lifecycle events — `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`, `forceDeleting`, `forceDeleted`.

```php
use Siro\Core\Observers\ModelObserver;
```

---

## Define Observer

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use Siro\Core\Observers\ModelObserver;
use Siro\Core\Model;

class ProductObserver extends ModelObserver
{
    public function creating(Model $model): void
    {
        // Auto-set slug before creation
        $model->setAttribute('slug', Str::slug($model->getAttribute('name')));
    }

    public function created(Model $model): void
    {
        // Log after creation
        Logger::info('Product created', ['id' => $model->id, 'name' => $model->name]);
    }

    public function updating(Model $model): void
    {
        // Prevent price drops below cost
        if ($model->getAttribute('price') < 10) {
            throw new \RuntimeException('Price too low');
        }
    }

    public function deleted(Model $model): void
    {
        // Cleanup related data
        Storage::delete('products/' . $model->id . '.jpg');
    }
}
```

---

## Register Observer

```php
// In routes/api.php or AppServiceProvider
use App\Models\Product;
use App\Observers\ProductObserver;

Product::observe(ProductObserver::class);
```

---

## Available Hooks (10)

```php
class MyObserver extends ModelObserver
{
    // Called in order:
    public function saving(Model $model): void   {}    // Before any save
    public function creating(Model $model): void {}    // Before insert
    public function created(Model $model): void  {}    // After insert
    public function saved(Model $model): void    {}    // After any save
    public function updating(Model $model): void {}    // Before update
    public function updated(Model $model): void  {}    // After update
    public function deleting(Model $model): void {}    // Before delete
    public function deleted(Model $model): void  {}    // After delete
    public function forceDeleting(Model $model): void {} // Before force delete
    public function forceDeleted(Model $model): void {}  // After force delete
}
```

---

## Use Cases

```php
// Before save — auto-fill fields
public function saving(Model $model): void
{
    if ($model->getAttribute('slug') === null) {
        $model->setAttribute('slug', Str::slug($model->getAttribute('title')));
    }
}

// Before delete — check constraints
public function deleting(Model $model): void
{
    if ($model->orders()->count() > 0) {
        throw new \RuntimeException('Cannot delete product with orders');
    }
}

// After create — trigger side effects
public function created(Model $model): void
{
    Event::dispatch('product.created', ['id' => $model->id]);
    Queue::push(new ProcessProductImages($model->id));
}
```
