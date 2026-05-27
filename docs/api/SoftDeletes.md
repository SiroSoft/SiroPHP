---
title: S of tD el et es
description: SiroPHP S of tD el et es reference
sidebar_position: 28
sidebar_label: S of tD el et es
---

# SoftDeletes Reference

## Overview

Soft deletes mark records as "deleted" without removing them from the database. Deleted records have a `deleted_at` timestamp set instead of being dropped.

```php
use Siro\Core\DB\SoftDeletes;
```

---

## Setup

### Migration

```php
// In your migration
$t->timestamp('deleted_at')->nullable();
```

### Model

```php
use Siro\Core\DB\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected string $table = 'products';
}
```

---

## Usage

```php
// Soft delete — sets deleted_at, record stays in DB
$product = Product::find(1);
$product->delete();
// Product still exists, but deleted_at is set

// Check if trashed
$product->trashed();  // true

// Include trashed in results
$products = Product::withTrashed()->get();

// Only trashed
$products = Product::onlyTrashed()->get();

// Restore
$product->restore();  // Sets deleted_at = null

// Force delete (permanent)
$product->forceDelete();  // Removes from DB entirely
```

---

## Query Scopes

```php
// Normal query — excludes soft-deleted
$products = Product::where('price', '>', 100)->get();

// Include deleted
$products = Product::withTrashed()
    ->where('category', 'electronics')
    ->get();

// Only deleted
$products = Product::onlyTrashed()->get();

// Count including deleted
$total = Product::withTrashed()->count();
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `delete()` | Soft delete (set `deleted_at`) |
| `forceDelete()` | Permanently delete from DB |
| `restore()` | Restore soft-deleted record |
| `trashed()` | Check if record is soft-deleted |
| `withTrashed()` | Include soft-deleted in query |
| `onlyTrashed()` | Only soft-deleted in query |
