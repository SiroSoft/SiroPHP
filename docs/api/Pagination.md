# Pagination Reference

## Overview

Pagination is built into the QueryBuilder and Model. No extra package needed.

```php
// Model
$products = Product::paginate(20, $page);

// QueryBuilder
$products = Product::query()
    ->where('price', '>', 100)
    ->orderBy('created_at', 'DESC')
    ->paginate(20, $page);
```

---

## Response Format

```json
{
    "success": true,
    "data": [
        { "id": 1, "name": "Product 1" },
        { "id": 2, "name": "Product 2" }
    ],
    "meta": {
        "page": 1,
        "per_page": 20,
        "total": 150,
        "last_page": 8
    }
}
```

---

## Controller Usage

```php
class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $page = max(1, $request->queryInt('page', 1));
        $perPage = min(100, max(1, $request->queryInt('per_page', 20)));

        $result = Product::query()
            ->where('status', 'active')
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage, $page);

        return $this->paginated(
            ProductResource::collection($result['data']),
            $result['meta'],
            'Products retrieved',
        );
    }
}
```

---

## With Resources

```php
$result = $this->service->getAll($page, $perPage);

return $this->paginated(
    UserResource::collection($result['data']),
    $result['meta'],
    'Users retrieved',
);
```

---

## Meta Structure

```php
$meta = $result['meta'];
// [
//   'page'      => 1,      // Current page
//   'per_page'  => 20,     // Items per page
//   'total'     => 150,    // Total items
//   'last_page' => 8,      // Last page number
// ]
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `Model::paginate(perPage, page)` | Paginate model query |
| `QueryBuilder::paginate(perPage, page)` | Paginate builder query |
| `Controller::paginated(data, meta, message)` | Return paginated response |
| `Response::paginated(data, meta, message)` | Static paginated response |
