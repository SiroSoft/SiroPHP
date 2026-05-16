# Validation Guide

## Available Validation Rules

| Rule | Description | Example |
|------|-------------|---------|
| `required` | Field must be present and non-empty | `'name' => 'required'` |
| `email` | Must be a valid email address | `'email' => 'email'` |
| `min:N` | Minimum string length | `'password' => 'min:8'` |
| `max:N` | Maximum string length | `'name' => 'max:255'` |
| `integer` | Must be an integer value | `'age' => 'integer'` |
| `numeric` | Must be numeric | `'price' => 'numeric'` |
| `in:a,b,c` | Must be one of the given values | `'status' => 'in:active,inactive'` |
| `date` | Must be a valid date | `'published_at' => 'date'` |
| `url` | Must be a valid URL | `'website' => 'url'` |
| `required_if:field,value` | Required when another field equals value | `'email' => 'required_if:has_email,yes'` |
| `regex:/pattern/` | Must match regex pattern | `'phone' => 'regex:/^[0-9]{10}$/'` |

### Multiple Rules

Use pipe `|` to chain rules:

```php
$rules = [
    'name' => 'required|min:2|max:120',
    'email' => 'required|email|max:255',
    'password' => 'required|min:8|max:255',
    'age' => 'integer|min:1|max:150',
    'status' => 'in:active,inactive,pending',
];
```

## Validator Usage

### Basic Validation

```php
use Siro\Core\Validator;

$data = ['email' => 'test@example.com', 'name' => 'John'];
$rules = ['email' => 'required|email', 'name' => 'required|min:2'];

$errors = Validator::make($data, $rules);

if ($errors === []) {
    // Validation passed
} else {
    // $errors = ['email' => ['Email is required'], 'name' => ['Name must be at least 2']]
}
```

### In Controllers

```php
use Siro\Core\Response;

final class UserController
{
    public function store(Request $request): Response
    {
        $request->validate([
            'name' => 'required|min:3|max:120',
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|max:255',
        ]);

        // Validation passed, access safe input
        $name = $request->string('name');
        $email = $request->string('email');

        // ... create user
    }
}
```

On failure, a `422` response is auto-returned:
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["Email is required"],
        "password": ["Password must be at least 8 characters"]
    }
}
```

## FormRequest Class

For complex validation, create a dedicated FormRequest class:

```php
use Siro\Core\Request;

final class StoreProductRequest extends Request
{
    public function validate(): array
    {
        return parent::validate([
            'name' => 'required|min:2|max:200',
            'price' => 'required|numeric|min:0',
            'stock' => 'integer|min:0',
            'category' => 'required',
            'status' => 'in:active,inactive',
        ]);
    }
}
```

Use in controller:

```php
public function store(StoreProductRequest $request): Response
{
    $data = $request->validate();
    // ...
}
```

## Custom Validation Rules

Extend the validator with custom rules:

```php
use Siro\Core\Validator;

Validator::extend('even', function (mixed $value): mixed {
    return ((int) $value) % 2 === 0
        ? true
        : ':field must be even';
});

// Usage
$errors = Validator::make(['num' => '3'], ['num' => 'even']);
// Returns: ['num' => ['num must be even']]
```

Use `true` for pass, or a string `:field` gets replaced with the field name.

## Error Responses

Validation errors return HTTP 422 with this format:

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": [
            "Field name is required",
            "Field name must be at least 3 characters"
        ],
        "email": [
            "Email is not a valid email address"
        ]
    }
}
```

Access errors in tests:

```php
$resp = $this->post('/api/products', []);
$resp->assertValidationError();
$json = $resp->json();
$errors = $json['errors'] ?? [];
$this->assertArrayHasKey('name', $errors);
```

## Best Practices

- Always validate on the server side — never trust client input.
- Use specific rules rather than generic ones (`email` instead of just `required`).
- Sanitize input with `$request->string()`, `$request->integer()`, `$request->boolean()`.
- Define custom rules with clear error messages using `:field` placeholder.
- Keep validation rules in FormRequest classes for reusable, single-responsibility controllers.
