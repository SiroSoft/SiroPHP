# Helpers Reference

## Overview

Siro provides two debug helpers available globally. No `use` statement needed.

---

## `dd()`

Dump variable(s) and stop execution.

```php
dd($variable);
dd($request, $user, $query);  // Multiple values
dd($data);
```

Output:

```
array:3 [
  "name" => "John"
  "email" => "john@test.com"
  "role" => "admin"
]
```

---

## `dump()`

Dump variable and continue execution (does not stop).

```php
dump($query->toSql());   // See the SQL being built
dump($user);             // Inspect user data
```

Useful inside loops or middleware for debugging without breaking the flow.

---

## When to Use

```php
// In Controller — debug request data
public function store(Request $request): Response
{
    dump($request->all());  // See what's coming in (continues)
    dd($request->user());   // See user, then stop
}

// In QueryBuilder — debug SQL
$query = Product::query()->where('price', '>', 100);
dump($query);               // See the query object
$results = $query->get();

// In tests
public function test_example(): void
{
    $response = $this->get('/api/users');
    dd($response->json());  // See full response
}
```

> **Pro tip**: In production, `dd()` and `dump()` are disabled when `APP_DEBUG=false`. Your app won't crash if you accidentally leave them in code.
