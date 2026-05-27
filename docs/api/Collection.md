---
title: C ol le ct io n
description: SiroPHP C ol le ct io n reference
sidebar_position: 2
sidebar_label: C ol le ct io n
---

# Collection API Reference

## Overview

Collections provide a fluent, chainable wrapper around arrays with 30+ helper methods. Think of them as "arrays with superpowers."

```php
use Siro\Core\Collection;
```

---

## Creating Collections

```php
// From array
$collection = new Collection([1, 2, 3, 4, 5]);

// Static factory
$collection = Collection::make([1, 2, 3]);

// From query results
$users = Collection::make($usersData);
```

---

## Basic Operations

```php
$col = Collection::make([1, 2, 3]);

$col->count();        // 3
$col->isEmpty();      // false
$col->isNotEmpty();   // true
$col->first();        // 1
$col->last();         // 3
```

---

## Transformations

### map

```php
$col = collect([1, 2, 3]);
$doubled = $col->map(fn($n) => $n * 2);
// [2, 4, 6]
```

### filter

```php
$col = collect([1, 2, 3, 4, 5]);
$evens = $col->filter(fn($n) => $n % 2 === 0);
// [2, 4]
```

### pluck

```php
$users = collect([
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
]);

$names = $users->pluck('name');
// ['Alice', 'Bob']

$keyed = $users->pluck('name', 'id');
// [1 => 'Alice', 2 => 'Bob']
```

### reduce

```php
$total = collect([1, 2, 3])->reduce(fn($carry, $n) => $carry + $n, 0);
// 6
```

---

## Filtering

### where

```php
$products = collect([
    ['name' => 'Laptop', 'price' => 999, 'stock' => 5],
    ['name' => 'Mouse', 'price' => 25, 'stock' => 100],
    ['name' => 'Keyboard', 'price' => 75, 'stock' => 0],
]);

$available = $products->where('stock', '>', 0);
// [['name' => 'Laptop', ...], ['name' => 'Mouse', ...]]

$cheap = $products->where('price', '<', 100);
// [['name' => 'Mouse', ...], ['name' => 'Keyboard', ...]]
```

### whereIn

```php
$filtered = $products->whereIn('name', ['Laptop', 'Keyboard']);
```

### reject

```php
$inStock = $products->reject(fn($p) => $p['stock'] === 0);
```

---

## Sorting

```php
$sorted = $collection->sort('price', 'asc');
$sorted = $collection->sortByDesc('price');
$reversed = $collection->reverse();
```

---

## Aggregates

```php
$prices = collect([10, 20, 30, 40]);

$prices->sum();      // 100
$prices->avg();      // 25
$prices->min();      // 10
$prices->max();      // 40
```

---

## Array Operations

```php
$col = collect([1, 2, 3]);

$col->push(4);          // [1, 2, 3, 4]
$col->pop();            // returns 4, collection becomes [1, 2, 3]
$col->shift();          // returns 1, collection becomes [2, 3]
$col->unshift(0);       // [0, 1, 2, 3]
```

### chunk

```php
$chunks = collect([1, 2, 3, 4, 5])->chunk(2);
// [[1, 2], [3, 4], [5]]
```

### slice

```php
$slice = collect([1, 2, 3, 4, 5])->slice(1, 3);
// [2, 3, 4]
```

### unique

```php
$unique = collect([1, 1, 2, 2, 3])->unique();
// [1, 2, 3]
```

---

## Serialization

```php
$col = collect(['name' => 'Siro', 'version' => '0.28']);

$col->toArray();         // ['name' => 'Siro', 'version' => '0.28']
$col->toJson();          // '{"name":"Siro","version":"0.28"}'
$col->implode(', ');     // 'Siro, 0.28'
```

---

## Chaining

Every method returns a Collection (or value), so you can chain:

```php
$result = collect($products)
    ->where('price', '>', 100)
    ->where('stock', '>', 0)
    ->sort('price')
    ->pluck('name');
```

---

## Tap & Pipe

```php
// tap — do something without breaking chain
$result = collect($data)
    ->tap(fn($c) => Logger::debug('Processing', ['count' => $c->count()]))
    ->map(fn($item) => $this->process($item));

// pipe — pass collection to a function
$result = collect($data)->pipe(fn($c) => $this->customTransform($c));
```

---

## Debug

```php
// Dump and die
collect($data)->dd();

// Dump and continue
collect($data)->dump();
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `make(array $items)` | Create new collection |
| `all()` | Get all items |
| `count()` | Item count |
| `isEmpty()` | Check if empty |
| `isNotEmpty()` | Check if not empty |
| `first()` | First item |
| `last()` | Last item |
| `get(key, default)` | Item by key |
| `set(key, value)` | Set by key |
| `push(value)` | Append |
| `pop()` | Remove last |
| `shift()` | Remove first |
| `unshift(value)` | Prepend |
| `pluck(column, key)` | Extract column |
| `map(callback)` | Transform each |
| `filter(callback)` | Filter items |
| `reject(callback)` | Remove by condition |
| `reduce(callback, initial)` | Reduce to single value |
| `each(callback)` | Iterate |
| `where(key, op, value)` | Filter by condition |
| `whereIn(key, values)` | Filter by values |
| `sort(column, direction)` | Sort |
| `sortByDesc(column)` | Sort desc |
| `reverse()` | Reverse order |
| `slice(offset, length)` | Slice |
| `chunk(size)` | Split into chunks |
| `unique(key)` | Unique items |
| `collapse()` | Flatten one level |
| `flatten(depth)` | Flatten nested |
| `combine(values)` | Keys + values |
| `keys()` | All keys |
| `values()` | All values |
| `merge(items)` | Merge collections |
| `toJson()` | JSON string |
| `toArray()` | PHP array |
| `implode(glue)` | Join to string |
| `sum(column)` | Sum |
| `avg(column)` | Average |
| `min(column)` | Minimum |
| `max(column)` | Maximum |
| `shuffle()` | Randomize |
| `random(count)` | Random items |
| `tap(callback)` | Chain break for side effects |
| `pipe(callback)` | Transform via callback |
| `dd()` | Dump and die |
| `dump()` | Dump and continue |
