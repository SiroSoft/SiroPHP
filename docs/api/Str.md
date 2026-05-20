# Str API Reference

## Overview

String manipulation utilities — slug generation, case conversion, truncation, pluralization. All methods are UTF-8 safe.

```php
use Siro\Core\Str;
```

---

## Case Conversion

```php
Str::camel('hello_world');    // 'helloWorld'
Str::studly('hello_world');   // 'HelloWorld'
Str::snake('helloWorld');     // 'hello_world'
Str::kebab('helloWorld');     // 'hello-world'
Str::title('hello world');    // 'Hello World'
Str::upper('hello');          // 'HELLO'
Str::lower('HELLO');          // 'hello'
Str::ucfirst('hello');        // 'Hello'
```

## Slug

```php
Str::slug('Hello World');             // 'hello-world'
Str::slug('Siro API v2!');            // 'siro-api-v2'
Str::slug('Cửa hàng sách', '-');      // 'cua-hang-sach'
```

## Truncation

```php
Str::limit('A very long string here...', 10);    // 'A very lon...'
Str::words('Hello world from Siro', 2);           // 'Hello world...'
```

## Inspection

```php
Str::contains('hello@test.com', '@');     // true
Str::startsWith('siro-api', 'siro');      // true
Str::endsWith('photo.jpg', '.jpg');       // true
Str::length('Hello');                     // 5
Str::isJson('{"key":"value"}');           // true
```

## Extraction

```php
Str::after('user@domain.com', '@');       // 'domain.com'
Str::before('user@domain.com', '@');      // 'user'
Str::substr('Hello World', 0, 5);         // 'Hello'
```

## Generation

```php
Str::random(16);    // 'a1b2c3d4e5f6g7h8'
Str::random(32);    // 32 random alphanumeric chars
```

## Modification

```php
Str::padBoth('Siro', 10, '-');      // '---Siro---'
Str::replace('Hello {name}', '{name}', 'World');  // Hello World
```

## Pluralization

```php
Str::plural('user');        // 'users'
Str::plural('category');    // 'categories'
Str::plural('child');       // 'children'
Str::singular('users');     // 'user'
Str::singular('categories'); // 'category'
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `slug(string $value, string $separator)` | URL-friendly slug |
| `camel(string $value)` | camelCase |
| `studly(string $value)` | StudlyCase |
| `snake(string $value)` | snake_case |
| `kebab(string $value)` | kebab-case |
| `title(string $value)` | Title Case |
| `upper(string $value)` | UPPERCASE |
| `lower(string $value)` | lowercase |
| `ucfirst(string $value)` | Ucfirst |
| `limit(string $value, int $limit, string $end)` | Truncate by chars |
| `words(string $value, int $words, string $end)` | Truncate by words |
| `contains(string $haystack, string $needle)` | Check substring |
| `startsWith(string $haystack, string $needle)` | Check prefix |
| `endsWith(string $haystack, string $needle)` | Check suffix |
| `after(string $value, string $search)` | Everything after |
| `before(string $value, string $search)` | Everything before |
| `substr(string $value, int $start, ?int $length)` | Substring |
| `random(int $length)` | Random string |
| `padBoth(string $value, int $length, string $pad)` | Pad both sides |
| `replace(string $value, string $search, string $replace)` | String replace |
| `length(string $value)` | String length |
| `isJson(string $value)` | Check if JSON |
| `plural(string $value)` | Pluralize |
| `singular(string $value)` | Singularize |
