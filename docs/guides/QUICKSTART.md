# SiroPHP Quick Start Guide

**Build a production-ready API in 5 minutes**

---

## 🚀 Installation (30 seconds)

### Option 1: Create New Project (Recommended)

```bash
composer create-project sirosoft/api my-api
cd my-api
php siro serve
```

Visit: http://localhost:8000

### Option 2: Add to Existing Project

```bash
composer require sirosoft/core
```

---

## 📋 5-Minute Tutorial

### Step 1: Setup Database (1 minute)

```bash
# Edit .env file
DB_CONNECTION=sqlite
# or
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=secret

# Run migrations
php siro migrate
```

### Step 2: Generate Authentication (1 minute)

```bash
php siro make:auth
php siro migrate
```

**Generated endpoints:**
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - Login and get JWT tokens
- `POST /api/auth/refresh` - Refresh access token
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user profile

### Step 3: Create Your First Resource (1 minute)

```bash
php siro make:crud products
php siro migrate
```

**Generated files:**
- ✅ `app/Models/Product.php` - Model with fillable fields
- ✅ `database/migrations/*_create_products_table.php` - Migration
- ✅ `app/Controllers/ProductController.php` - Full CRUD controller
- ✅ `app/Resources/ProductResource.php` - Resource transformer
- ✅ Routes auto-injected in `routes/api.php`
- ✅ `tests/Feature/ProductTest.php` - Integration tests

### Step 4: Test Your API (1 minute)

```bash
# Register a user
php siro api:test POST /api/auth/register \
  name="John Doe" \
  email="john@example.com" \
  password="secret123" \
  password_confirmation="secret123"

# Login and save token
php siro api:test POST /api/auth/login \
  email="john@example.com" \
  password="secret123" \
  --as=user

# Create a product (auto uses saved token)
php siro api:test POST /api/products \
  name="Laptop" \
  price="999.99" \
  --as=user

# List products
php siro api:test GET /api/products --as=user
```

### Step 5: Generate API Documentation (1 minute)

```bash
php siro make:openapi --with-swagger
```

Visit Swagger UI: http://localhost:8000/docs/swagger/

**Done!** You now have a production-ready API with:
- ✅ JWT authentication
- ✅ Full CRUD for products
- ✅ API documentation
- ✅ Tests
- ✅ Validation
- ✅ Error handling

---

## 🎯 Next Steps

### Add Relationships

```php
// app/Models/Product.php
public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}

public function reviews(): HasMany
{
    return $this->hasMany(Review::class);
}

// Usage with eager loading
$products = Product::with('category', 'reviews')->paginate(20);
```

### Add Custom Validation

```php
// app/Controllers/ProductController.php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id',
        'sku' => 'required|unique:products,sku',
    ]);
    
    $product = Product::create($validated);
    
    return Response::created(new ProductResource($product));
}
```

### Add Middleware

```php
// routes/api.php
Route::post('/products', [ProductController::class, 'store'])
    ->middleware(['auth:user,admin'])  // Require authentication
    ->throttle(10, 1);  // Rate limit: 10 requests per minute
```

### Queue Heavy Operations

```php
// Instead of sending email synchronously
Mail::to($user)->subject('Order Created')->html($html)->queue();

// Process queue
php siro queue:work
```

---

## 📚 Essential Commands

### Development

```bash
php siro serve              # Start dev server
php siro live               # Dev server with auto-reload
php siro route:list         # List all routes
php siro test               # Run all tests
```

### Code Generation

```bash
php siro make:model User
php siro make:controller UserController
php siro make:migration create_users_table
php siro make:resource UserResource
php siro make:test UserApi
php siro make:factory User
php siro make:job SendEmail
php siro make:mail WelcomeMail
php siro make:event UserCreated
```

### Database

```bash
php siro migrate            # Run migrations
php siro migrate:rollback   # Rollback last migration
php siro db:seed            # Run seeders
php siro db:show users      # Inspect table
```

### Debugging

```bash
php siro log:trace <id>     # View trace details
php siro log:replay <id>    # Replay request
php siro slow               # Show slow requests
php siro api:test GET /api/users  # Test endpoint
```

### Performance

```bash
php siro benchmark          # Run benchmarks
php siro config:cache       # Cache configuration
php siro optimize           # Optimize for production
php siro env:check          # Validate environment
```

### Deployment

```bash
php siro deploy             # Deploy application
php siro down               # Enable maintenance mode
php siro up                 # Disable maintenance mode
php siro storage:link       # Create storage symlink
```

---

## 🔍 Common Tasks

### Add File Upload

```php
// Controller
$file = $request->file('avatar');
$path = $file->store('avatars');

return Response::json([
    'url' => Storage::url($path),
]);
```

### Add Pagination

```php
// Controller
$products = Product::paginate(20, $page);

return Response::json([
    'data' => ProductResource::collection($products['data']),
    'meta' => $products['meta'],
]);
```

### Add Search/Filter

```php
// Controller
$query = Product::query();

if ($request->has('category_id')) {
    $query->where('category_id', $request->int('category_id'));
}

if ($request->has('search')) {
    $query->where('name', 'LIKE', '%' . $request->string('search') . '%');
}

$products = $query->paginate(20);
```

### Add Soft Deletes

```php
// Model
use Siro\Core\DB\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
}

// Controller
Product::withTrashed()->get();  // Include deleted
Product::onlyTrashed()->get();  // Only deleted
$product->restore();             // Restore deleted
```

---

## 🛠️ Troubleshooting

### Problem: "Class not found"

**Solution:**
```bash
composer dump-autoload
```

### Problem: "Migration failed"

**Solution:**
```bash
php siro migrate:rollback
# Fix migration file
php siro migrate
```

### Problem: "Token expired"

**Solution:**
```bash
# Refresh token
php siro api:test POST /api/auth/refresh --as=user
```

### Problem: "Route not found"

**Solution:**
```bash
# Check routes
php siro route:list

# Clear cache
rm storage/cache/routes.php
```

---

## 📖 Learn More

- **[Architecture Guide](ARCHITECTURE.md)** - Understand design decisions
- **[Security Guide](SECURITY.md)** - Security best practices
- **[Performance Guide](PERFORMANCE.md)** - Optimization tips
- **[API Reference](api/)** - Detailed API documentation
- **[Examples](examples/)** - Real-world code samples

---

## 💡 Pro Tips

1. **Use `make:crud` for rapid development** - Generates full CRUD in 2 seconds
2. **Enable config caching in production** - `php siro config:cache`
3. **Use eager loading to prevent N+1 queries** - `Model::with('relation')`
4. **Queue heavy operations** - Don't block HTTP requests
5. **Monitor slow requests** - `php siro slow`
6. **Write tests early** - `php siro make:test ProductApi`
7. **Generate API docs automatically** - `php siro make:openapi`
8. **Use trace IDs for debugging** - Every response includes `X-Siro-Trace-Id`

---

**Happy coding! 🚀**

For questions, visit: https://github.com/SiroSoft/SiroPHP/discussions
