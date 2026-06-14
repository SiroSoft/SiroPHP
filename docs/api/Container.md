---
title: C on ta in er
description: SiroPHP C on ta in er reference
sidebar_position: 5
sidebar_label: C on ta in er
---

# Container API Reference

## Overview

The Container is Siro's Dependency Injection container with autowiring, singleton resolution, contextual bindings, and circular dependency detection.

```php
use Siro\Core\Container;

$container = Container::getInstance();
```

---

## Basic Usage

### Resolve from Container

```php
$service = $container->make(UserService::class);

// With parameters
$controller = $container->make(ProductController::class);
```

### Singleton Access

```php
$container = Container::getInstance();
```

All resolutions are singletons by default within a single request lifecycle.

---

## Binding

### Bind Interface to Implementation

```php
$container->bind(UserRepositoryInterface::class, UserRepository::class);

// Resolve returns UserRepository instance
$repo = $container->make(UserRepositoryInterface::class);
```

### Bind Singleton

```php
$container->singleton(CacheService::class, function ($c) {
    return new CacheService(config('cache.ttl'));
});
```

### Bind Instance

```php
$container->instance('db', Database::connection());
```

---

## Contextual Binding

Bind different implementations based on which class consumes them:

```php
$container->when(OrderController::class)
    ->needs(OrderProcessorInterface::class)
    ->give(ExpressOrderProcessor::class);

$container->when(BackofficeController::class)
    ->needs(OrderProcessorInterface::class)
    ->give(BatchOrderProcessor::class);
```

---

## Autowiring

The container automatically resolves constructor parameters:

```php
class ProductController
{
    // Automatically resolved from container
    public function __construct(
        private readonly ProductService $service,
        private readonly Logger $logger,
    ) {}
}
```

### Primitive Values

For scalar parameters, use contextual binding:

```php
$container->when(ProductController::class)
    ->needs('$perPage')
    ->give(20);
```

---

## Tags

Tag related services for batch retrieval:

```php
$container->tag([
    ReportGenerator::class,
    PdfExporter::class,
    CsvExporter::class,
], 'exports');

// Retrieve all tagged services
$exporters = $container->tagged('exports');
```

---

## Rebound Callbacks

Execute code when a binding is resolved or rebound:

```php
$container->rebinding('cache', function ($c, $instance) {
    // Called when 'cache' binding is overwritten
    $c->make(Logger::class)->debug('Cache driver changed');
});
```

---

## Circular Dependency Detection

```php
class A { public function __construct(B $b) {} }
class B { public function __construct(A $a) {} }

$container->make(A::class);
// Throws RuntimeException: Circular dependency detected: A → B → A
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `getInstance()` | Get singleton container instance |
| `make(string $class)` | Resolve a class from container |
| `bind(string $abstract, string\|Closure $concrete)` | Register binding |
| `singleton(string $abstract, string\|Closure $concrete)` | Register singleton binding |
| `instance(string $abstract, mixed $instance)` | Set concrete instance |
| `when(string $class)` | Start contextual binding |
| `needs(string $parameter)` | Specify parameter for contextual binding |
| `give(mixed $implementation)` | Specify implementation for contextual binding |
| `tag(array $classes, string $tag)` | Tag services |
| `tagged(string $tag)` | Get all tagged services |
| `rebinding(string $abstract, Closure $callback)` | Register rebound callback |
| `has(string $abstract)` | Check if binding exists |
| `forgetInstance(string $abstract)` | Remove resolved instance |
| `clearResolved()` | Clear all resolved instances |
