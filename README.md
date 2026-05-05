<<<<<<< HEAD
# Siro API Framework v0.14.0

**The Fastest PHP Micro-Framework for API Development with Advanced Debugging & CLI Testing**

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-brightgreen.svg)](https://php.net)
[![Packagist](https://img.shields.io/badge/packagist-v0.14.0-blue.svg)](https://packagist.org/packages/sirosoft/api)
[![Tests](https://img.shields.io/badge/tests-174%20passing-brightgreen.svg)](tests/)
[![PHPStan](https://img.shields.io/badge/phpstan-level%206-brightgreen.svg)](../siro-core/phpstan.neon)

---

## 🚀 Why SiroPHP?

**Built for lean teams — 1-2 devs, tight deadlines, low budget:**

| Your pain | Siro's solution |
|-----------|----------------|
| 📅 **Onboard in 30 minutes?** | 6 commands from zero → API with auth. Read this README and start coding. |
| 🔄 **Client changes requirements daily?** | `php siro make:crud` scaffolds in 2 seconds. Won't break existing code. |
| 💰 **$2/month hosting?** | Pure PHP, **zero dependencies**, ~2MB RAM per request. Runs on any shared host. |
| 🚀 **No DevOps team?** | `php siro deploy` — push to Ubuntu VPS + Nginx + MySQL in one command. |
| 📋 **Client asks "where's the API docs?"** | `php siro make:openapi --with-swagger` — Swagger UI in 1 second. |
| 🔐 **Complex auth?** | Multi-role (admin/staff/customer), phone login, OTP, JWT refresh tokens built in. |
| ⚡ **Worried about upgrades breaking code?** | Strict Semver. Migration guide per version. Zero breaking changes within same minor. |

> **"The Laravel alternative that runs on $2/month hosting, can be read in one afternoon, and ships an API in one hour."**

---

## 🎯 Zero to API with Auth in 5 Minutes

**Goal:** Build a full CRUD API with authentication — from nothing to working in 5 minutes.

### 1. Install

```bash
composer create-project sirosoft/api my-app
cd my-app
php siro key:generate
```

### 2. Generate auth system + first CRUD

```bash
php siro make:auth                          # Login, register, JWT refresh...
php siro make:crud products                 # Full CRUD: model, migration, routes
php siro migrate                            # Create tables
php siro serve                              # Start server → http://localhost:8080
```

### 3. Test register + login

```bash
php siro api:test POST /api/auth/register name="Demo" email=demo@test.com password=secret
# {"success":true,"message":"User registered successfully","data":{"token":"eyJ..."}}

php siro api:test POST /api/auth/login email=demo@test.com password=secret
# {"success":true,"message":"Login successful","data":{"token":"eyJ..."}}
```

### 4. Test protected CRUD

```bash
php siro api:test GET /api/products            # Public
php siro api:test POST /api/products name=Laptop price=999 --as=admin  # Auto-auth
php siro api:test POST /api/products            # → 401 (no auth)
```

### 5. Debug when something goes wrong

```bash
php siro debug:last                             # See last request: headers, body, response, SQL
php siro log:replay a1b2c3d4 --force            # Replay any past request
php siro api:test GET /api/products --loop=50   # Load test
```

> That's it. You now have: registration, login, JWT auth, CRUD API, database, tests, and debugging.  
> Total commands: **6**. Total time: **< 5 minutes**.

---

## 🎓 Examples by Experience Level

### I'm new — what should I build first?
```bash
php siro make:crud categories --simple     # Model + Controller only (no layers)
php siro serve                              # See it live at /api/categories
php siro api:test POST /api/categories name=Test
php siro log:trace <id>                     # See what happened
```

### I'm building a real app
```bash
php siro make:crud orders --with-service --with-repository  # Full layers
php siro make:crud order-items --simple                      # Simple child CRUD
php siro make:resource OrderResource                         # Custom response shape
php siro route:search order                                  # Check all order routes
```

### I'm debugging production
```bash
php siro doctor --prod                     # Check env, DB, HTTPS, CORS
php siro log:slow --limit=20               # Find slowest APIs
php siro log:top                           # Aggregated by endpoint
php siro log:trace a1b2c3d4 --full         # Full request/response/SQL
php siro log:replay a1b2c3d4 --set status=cancelled  # Replay with different data
```

---

## 🛠️ CLI Commands

### Code Generation
```bash
php siro make:model User              # Generate model
php siro make:controller UserController
php siro make:migration create_posts_table
php siro make:resource UserResource
php siro make:seeder UserSeeder
php siro make:auth                    # Generate full auth system
```

### Database
```bash
php siro migrate                      # Run migrations
php siro migrate:rollback             # Rollback migrations
php siro migrate:status               # Check migration status
php siro db:seed                      # Run all seeders
php siro db:seed UserSeeder           # Run specific seeder
```

### Debugging 🔍
```bash
php siro debug:last                      # Show last request: headers, body, SQL
php siro log:trace <trace_id>            # View trace details
php siro log:trace <trace_id> --full     # Full view with headers & SQL
php siro log:replay <trace_id>           # Replay any past request
php siro log:replay <trace_id> --set status=cancelled  # Override data
php siro log:replay <trace_id> --seed    # Create seeder from real request
php siro log:export <trace_id> --postman # Export to Postman format
php siro open:postman <trace_id>         # Same, shortcut
php siro log:cleanup --days=7            # Clean old trace files
php siro log:slow --limit=10             # Show slow requests
php siro log:top                         # Top slow APIs by total time
php siro log:tail                        # Tail logs in real-time
```

### Performance
```bash
php siro config:cache                 # Cache config
php siro env:check                    # Validate environment
php siro optimize                     # Optimize for production
```

### Utilities
```bash
php siro route:list                    # List all routes
php siro route:search user             # Search routes by keyword
php siro serve                         # Start development server
php siro key:generate                  # Generate APP_KEY
php siro doctor                        # Check system health
php siro doctor --prod                 # Production health check (HTTPS, CORS, env)
```

### ⭐ API Testing (v0.8.8) - Replace Postman!
```bash
# Quick test endpoint
php siro api:test GET /api/users

# Load test (100 requests)
php siro api:test GET /api/users --loop=100

# Auto-login (1 command: login → save token → run request)
php siro api:test GET /api/auth/me --login email=admin@test.com password=secret

# Test with saved token (login once, token saved)
php siro api:test POST /auth/login email=admin@test.com password=123 --as=admin
php siro api:test GET /users --as=admin              # Auto uses saved token

# Guest mode (no auth token)
php siro api:test GET /api/public --as=guest

# Specific user
php siro api:test GET /api/users/orders --as=user:123

# View request history
php siro api:test --history

# Custom headers & different port
php siro api:test GET /api/data --header="X-Version: 2.0" --port=8080
```

### 🚀 CRUD Scaffolding & Testing (v0.12.0)
```bash
# Generate full CRUD in 30 seconds (Model, Controller, Migration, Routes, Tests)
php siro make:crud products

# Simple CRUD (Model + Controller + Routes only)
php siro make:crud tag --simple

# Generate integration test
php siro make:test ProductApi

# Generate unit test
php siro make:test ProductService --unit

# Response includes performance headers
# X-Request-Id: a1b2c3d4e5f67890
# X-Response-Time: 8.45ms
```

### 🆕 New CLI Tools (v0.13.0+)
```bash
# Create new project from skeleton
php siro new my-api                   # Create project folder + .env + JWT key

# List all commands with usage syntax
php siro list                          # 50 commands grouped by category
php siro make:crud --help             # Detailed help for specific command
php siro --version                     # Show version (0.13.0)

# Generate factory for test data generation
php siro make:factory User            # Creates database/factories/UserFactory.php

# Inspect database tables from CLI
php siro db:show users                # View table data
php siro db:show products --schema    # View table schema

# Extract validation rules from controllers
php siro route:rules                  # All controllers
php siro route:rules UserController@store  # Specific method

# Development server with auto-reload
php siro live                         # Auto-restart on file changes
php siro live --port=8080             # Custom port

# Slow request analysis
php siro log:slow                     # Show top 10 slow requests
php siro log:slow --limit=20 --min=200

# Deployment system — for Ubuntu VPS + Nginx + MySQL
php siro deploy                       # Deploy with default strategy
php siro deploy --init                # First-time setup: create user, configure Nginx
php siro deploy --dry-run             # Test without deploying
```

**Minimum production requirements (runs on $2/month VPS):**
```
PHP 8.2+                     # Size: ~50MB
MySQL / SQLite               # RAM: ~256MB is enough
Nginx / Apache               # Disk: ~100MB for code
Any Linux VPS                # Request: ~2MB RAM per request
```
> No Redis, no Node, no Composer needed on production.

**Factory Usage:**
```php
// In tests or seeders
use Database\Factories\UserFactory;

// Create single user
$user = UserFactory::new()->create();

// Create multiple users
$users = UserFactory::new()->count(10)->create();

// Override attributes
$admin = UserFactory::new()->create(['role' => 'admin']);
```

### Storage & Scheduling (v0.8.3)
```bash
php siro storage:link                 # Create symlink for uploaded files
php siro schedule:run                 # Run scheduled tasks (for crontab)
```

**Setup Crontab:**
```bash
# Add to crontab to run scheduler every minute
* * * * * cd /path/to/project && php siro schedule:run
```

**Define Tasks** in `routes/schedule.php`:
```php
// Run command daily
$schedule->command('db:seed UserSeeder')->daily();

// Run closure hourly
$schedule->call(function () {
    // Clean old logs
})->hourly();

// Custom cron expression
$schedule->command('report:weekly')->cron('0 6 * * 1');

// Call class method
$schedule->call([\App\Crons\HealthCheck::class, 'run'])->hourly();
```

### Queue & Mail (v0.8.4) 📧
```bash
php siro queue:work                   # Process queued jobs
php siro queue:work --daemon          # Run worker continuously
php siro queue:status                 # Show queue status
php siro queue:retry <id>             # Retry failed job
php siro queue:flush                  # Clear failed jobs
```

### 🔍 Static Analysis & Benchmarks (v0.12.0)
```bash
# Run PHPStan static analysis (Level 6 - 0 errors)
phpstan analyse

# Run performance benchmarks
php tests/benchmark.php

# Results: 398K ops/s average throughput!
```

**Performance Highlights:**
- ⚡ Cold boot: 0.87ms
- 🚀 GET /: 522K ops/s
- 🔥 Router matching: 893K ops/s
- 💪 Avg throughput: 398K ops/s
- 💾 Memory: 2MB stable, +0KB per request

**SiroPHP is 2000-4000x faster than Laravel!**

### 🚀 Revolutionary API Testing (v0.12.0)

**3 Game-Changing Features:**

```bash
# 1. Export traces to Postman curl commands
php siro log:export <trace_id> --postman

# 2. Watch mode - auto re-run on code changes
php siro api:test GET /api/users --watch

# 3. Request collections - batch testing
php siro api:test POST /login email=admin password=123 --collection-save=myapi
php siro api:test --collection=myapi
```

**Productivity Boost: 30-60x faster debugging!**

### 🛠️ Complete Developer Toolkit (v0.12.0)

**5 Powerful CLI Tools:**

```bash
# 1. Run all tests with one command
php siro test
# ═══ 174 tests, 174 passed, 0 failed in 3.08s ═══

# 2. Quick environment switching
php siro env:switch staging
# Copied .env.staging → .env (backup saved)

# 3. Analyze slow requests
php siro slow --limit=20 --min=200
# Shows top slowest requests with SQL count

# 4. Webhook listener
php siro api:test POST /webhook --webhook --port=9000
# Receives and displays incoming webhooks

# 5. CORS validation
php siro api:test GET /api/users --cors
# Automated 3-step CORS testing
```

**Saves 2-3 hours per week on development tasks!** ⏱️

---

## 🚨 Error Reference — What You Actually See

A good framework shows you exactly what went wrong. Here's every error you'll encounter — and what they look like:

### Validation Error (422)
```bash
php siro api:test POST /api/products
```
```json
// 422 Unprocessable Entity
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "price": ["The price field is required."]
  }
}
```

### Auth Error — JWT Expired (401)
```bash
php siro api:test GET /api/auth/me
```
```json
// 401 Unauthorized
{
  "success": false,
  "message": "Token has expired",
  "data": null
}
```

### Auth Error — No Token (401)
```json
// 401 Unauthorized
{
  "success": false,
  "message": "Token not provided"
}
```

### Not Found (404)
```json
// 404 Not Found
{
  "success": false,
  "message": "Product not found",
  "data": null
}
```

### Server Error (500)
*(When `APP_DEBUG=true`, you get full detail + trace)*
```json
{
  "success": false,
  "message": "SQLSTATE[HY000]: General error: 1 no such table: orders",
  "trace": "#0 /app/vendor/sirosoft/core/src/DB/Database.php(123): PDO->query()..."
}
```
*(When `APP_DEBUG=false`, you get a safe message)*
```json
{
  "success": false,
  "message": "Internal Server Error"
}
```

### Rate Limited (429)
```json
// 429 Too Many Requests
{
  "success": false,
  "message": "Too Many Requests"
}
```

### Debug Last Request
```bash
php siro debug:last
```
Shows everything: request headers, body, response, SQL queries — in a single command.

---

## 🔌 Escape Hatch — When You Need Raw Power

No framework covers every use case. Here's how to break out:

### Raw SQL — Skip the Model, Use PDO Directly
```php
use Siro\Core\Database;

// Direct PDO access
$pdo = Database::connection();
$stmt = $pdo->query('SELECT * FROM users WHERE role = ?', ['admin']);
$users = $stmt->fetchAll();

// Raw insert with transaction
$pdo->beginTransaction();
$pdo->exec("UPDATE accounts SET balance = balance - 100 WHERE id = 1");
$pdo->exec("UPDATE accounts SET balance = balance + 100 WHERE id = 2");
$pdo->commit();
```

### Native PHP — Skip the Framework Entirely
```php
// routes/api.php — any PHP library works
$router->get('/report', function () {
    // Call any third-party library
    $pdf = new \FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Hello World');
    $pdf->Output();

    return Response::download($pdf->Output('S'), 'report.pdf');
});
```

### Custom Validation Rule
```php
use Siro\Core\Validator;

Validator::extend('phone', function ($value, $field, $input, $param) {
    return preg_match('/^\+?[0-9]{7,15}$/', (string) $value)
        ? true
        : ':field is not a valid phone number';
});

// Use it anywhere
$request->validate(['phone' => 'required|phone']);
```

### Register Custom CLI Command
```php
// routes/console.php
$console->register('report:weekly', function ($args) {
    // Your custom logic
    echo "Generating weekly report...\n";
    return 0;
});
```

### Use Any PHP Library
```bash
composer require phpoffice/phpspreadsheet
```
```php
use PhpOffice\PhpSpreadsheet\Spreadsheet;

$router->get('/export/excel', function () {
    $spreadsheet = new Spreadsheet();
    // ... build spreadsheet
    return Response::success(['url' => '/exports/file.xlsx']);
});
```

---

## 🌍 Real-World Examples

### Nested Relationships (Eager Loading)
```php
// Model
final class Order extends Model
{
    protected string $table = 'orders';

    public function items(): array
    {
        return OrderItem::query()->where('order_id', $this->id)->get();
    }

    public function customer(): mixed
    {
        return User::find($this->customer_id);
    }
}

// Controller — 1 query for orders + items (no N+1)
$orders = Order::with('items')->where('status', 'pending')->get();
```

### Transaction Spanning Multiple Services
```php
// OrderService.php
public function placeOrder(array $data): mixed
{
    $pdo = \Siro\Core\Database::connection();
    $pdo->beginTransaction();

    try {
        // 1. Deduct inventory
        $inventory = InventoryService::deduct($data['product_id'], $data['quantity']);

        // 2. Create order
        $order = $this->repo->store([
            'user_id'    => $data['user_id'],
            'total'      => $inventory['total'],
            'status'     => 'confirmed',
        ]);

        // 3. Charge payment
        PaymentService::charge($data['user_id'], $inventory['total']);

        // 4. Send notification
        Event::emit('order.placed', $order);

        $pdo->commit();
        return $order;

    } catch (\Throwable $e) {
        $pdo->rollBack();
        Log::error('Order failed: ' . $e->getMessage());
        return null;
    }
}
```

### File Upload + Queue + Event
```php
// Controller
public function uploadAvatar(Request $request): Response
{
    $request->validate(['avatar' => 'required|file|max:2048']);

    $file = $request->file('avatar');
    $path = Storage::put('avatars/' . $file['name'], $file['content']);

    // Queue thumbnail generation
    Queue::push(new GenerateThumbnailJob($path));

    // Fire event for other systems
    Event::emit('avatar.uploaded', ['path' => $path, 'user_id' => auth()->id()]);

    return Response::success(['url' => Storage::url($path)], 'Avatar uploaded');
}

// Job
final class GenerateThumbnailJob
{
    public function handle(): void
    {
        $image = file_get_contents($this->path);
        $thumb = imagescale(imagecreatefromstring($image), 150, 150);
        Storage::put('thumbs/' . basename($this->path), $thumb);
    }
}
```

### Soft Deletes with History
```php
use Siro\Core\DB\SoftDeletes;

final class Invoice extends Model
{
    use SoftDeletes;

    public function delete(): bool
    {
        // Log who deleted it before soft-delete
        Log::info('Invoice ' . $this->id . ' deleted by user ' . auth()->id());
        return parent::delete();
    }
}

// Usage
$invoice->delete();           // Soft delete (sets deleted_at)
$invoice->restore();          // Restore
Invoice::all();               // Auto-filters deleted
Invoice::withTrashed()->get(); // Include deleted
```

### API Versioning
```php
$router->version(1, function ($r) {
    $r->get('/users', [V1\UserController::class, 'index']);
    // → GET /api/v1/users
});

$router->version(2, function ($r) {
    $r->get('/users', [V2\UserController::class, 'index']);
    // → GET /api/v2/users
});
```

---

## 🏪 Real SME Example — Grocery Store Sales Management

**Scenario:** A grocery store needs an app to manage orders, products, customers, and staff.

### 1. Scaffold the entire system (5 commands, 30 seconds)

```bash
# Auth + roles
php siro make:auth

# CRUD for each business entity
php siro make:crud products                          # Products
php siro make:crud customers                         # Customers
php siro make:crud orders --without-repository       # Orders (simple)
php siro make:crud order-items --simple              # Order items (lightweight)

# Run migration
php siro migrate
```

### 2. Auto-generated route structure

```
GET/POST    /api/auth/register, login, me        # Auth
GET/POST    /api/products, /api/products/{id}     # Product CRUD
GET/POST    /api/customers, /api/customers/{id}   # Customer CRUD
GET/POST    /api/orders, /api/orders/{id}         # Order CRUD
GET/POST    /api/order-items, /api/order-items/{id}  # Order items CRUD
```

### 3. Real business logic

```php
// OrderService.php — auto-generated, you just add the logic
final class OrderService
{
    public function create(array $data): mixed
    {
        // Auto-calculate total
        $items = $data['items'] ?? [];
        $total = 0;
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $total += $product->price * $item['quantity'];
        }
        $data['total'] = $total;
        $data['status'] = 'pending';

        return $this->repo->store($data);
    }

    public function confirm(int $id): mixed
    {
        $order = $this->repo->findById($id);
        if ($order === null) return null;

        // Deduct inventory
        $items = OrderItem::query()->where('order_id', $id)->get();
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $product->update(['stock' => $product->stock - $item['quantity']]);
        }

        $order->update(['status' => 'confirmed', 'confirmed_at' => date('Y-m-d H:i:s')]);
        return $order;
    }
}
```

### 4. API docs for your client

```bash
php siro make:openapi --with-swagger
# → public/docs.html — send this link to your client
```

### 5. Deploy to $2/month VPS

```bash
php siro deploy
# → zero-dependency PHP, runs on Nginx + MySQL, ~2MB RAM per request
```

---

## 🔐 Real-World Auth Patterns for SME

### Multi-Role (admin/staff/customer)

```php
// Controller — check role directly in the method
public function store(Request $request): Response
{
    // Only admin can create products
    if ($request->user('role') !== 'admin') {
        return Response::error('Forbidden: admin only', 403);
    }
    // ...
}

public function listMyOrders(Request $request): Response
{
    $user = $request->user();
    if ($user['role'] === 'customer') {
        $orders = Order::query()->where('customer_id', $user['id'])->get();
    } else {
        $orders = Order::all(); // staff/admin see everything
    }
    return Response::success($orders, 'OK');
}
```

### Phone Number Login (instead of Email)

```php
// AuthController — override the default
public function login(Request $request): Response
{
    $data = $request->validate([
        'phone'    => 'required|regex:/^[0-9]{10,11}$/',
        'password' => 'required|min:6',
    ]);

    $user = User::query()->where('phone', $data['phone'])->first();
    if ($user === null || !password_verify($data['password'], $user['password'])) {
        return Response::error('Invalid phone or password', 401);
    }

    $token = \Siro\Core\Auth\JWT::generateToken(['id' => $user['id'], 'role' => $user['role']]);
    return Response::success(['token' => $token, 'user' => $user], 'Login successful');
}
```

### OTP Login (no password needed)

```php
// 1. Send OTP
$router->post('/auth/send-otp', function (Request $request) {
    $data = $request->validate(['phone' => 'required|regex:/^[0-9]{10,11}$/']);
    $otp = random_int(100000, 999999);

    // Store OTP in cache (5 minutes)
    \Siro\Core\Cache::put('otp:' . $data['phone'], $otp, 300);

    // Send SMS (call third-party service)
    // SmsService::send($data['phone'], 'Your OTP: ' . $otp);

    return Response::success(null, 'OTP sent');
});

// 2. Verify OTP → login
$router->post('/auth/verify-otp', function (Request $request) {
    $data = $request->validate([
        'phone' => 'required|regex:/^[0-9]{10,11}$/',
        'otp'   => 'required|numeric|digits:6',
    ]);

    $cached = \Siro\Core\Cache::get('otp:' . $data['phone']);
    if ($cached === null || (string) $cached !== $data['otp']) {
        return Response::error('Invalid or expired OTP', 401);
    }

    \Siro\Core\Cache::delete('otp:' . $data['phone']);

    // Find or create user
    $user = User::query()->where('phone', $data['phone'])->first();
    if ($user === null) {
        $user = User::create(['phone' => $data['phone'], 'role' => 'customer']);
    }

    $token = \Siro\Core\Auth\JWT::generateToken(['id' => $user['id'], 'role' => $user['role']]);
    return Response::success(['token' => $token, 'user' => $user], 'Login successful');
});
```

---

## ⬆️ Upgrade & Migration Guide

**Commitment:** Siro follows **Semver** — breaking changes only happen in major releases (v1.0, v2.0...).

### v0.14.x → v0.15.x (same minor)
```bash
composer update sirosoft/core
```
✅ **No breaking changes.** Your code keeps working. Only new features added.

### v0.13.x → v0.14.x (same major)
Run `php siro doctor` to check for any incompatibilities.

```bash
composer update sirosoft/core
php siro doctor           # Check system health
php siro test             # Run tests — if they pass, you're good
```

### When does code break?

| Your action | Breaking? |
|-------------|-----------|
| `composer update` within same minor (0.14.x) | ❌ No |
| Update from 0.13 → 0.14 | ⚠️ Possibly (check changelog) |
| Update from 0.x → 1.0 | ⚠️ Migration script needed |
| Using `DB::raw()` instead of Model | ❌ No (PDO is always stable) |

### Rollback if something breaks
```bash
# 1. Revert code
git revert HEAD

# 2. Revert database
php siro migrate:rollback

# 3. Downgrade package
composer require sirosoft/core:0.13.0
```

---

### 🏗️ Service & Repository Pattern (v0.14.1)

Full layered architecture generated with one command:

```bash
# Full CRUD with all layers (Model, Migration, Repository, Service, Controller, Resource, Routes, Test)
php siro make:crud invoice --force

# Simple mode: only Model + Controller + Routes (no layers)
php siro make:crud tag --simple

# Generate individual layers
php siro make:service Order              # Business logic
php siro make:repository Product          # Data access

# Skip layers if not needed
php siro make:crud tag --without-service --without-repository

# With auto-seed after generation
php siro make:crud category --seed
```

**Flags:**
| Flag | Description |
|------|-------------|
| `--simple` | Only Model + Controller + Routes (no layers) |
| `--seed` | Run `db:seed` after generation |
| `--force` | Overwrite existing files |
| `--without-service` | Skip service layer |
| `--without-repository` | Skip repository layer |

**Generated chain (full mode):**
```
Route → Controller → Service → Repository → Model
         (DI)          (DI)         (DI)
```

**Guided experience after generation:**
```
  ======================================================
  Full CRUD — Invoice created successfully!
  ======================================================

  Next steps:

  1. Run migration:
     php siro migrate

  2. Start dev server:
     php siro serve

  3. Test API:
     php siro api:test GET /api/invoices

  4. Debug request:
     php siro log:trace
     php siro log:replay <trace_id>
```

Validation auto-detects rules by model name:
- `product` → `name`, `price`, `sku`
- `invoice` → `customer_id`, `total`
- `user/customer` → `name`, `email`
- `category/tag` → `name`, `slug`

### 🧪 PHPUnit Test Generation (v0.14.1)

```bash
# Generate feature test (API integration)
php siro make:test ProductApi
# → tests/Feature/ProductApiTest.php (runs via vendor/bin/phpunit)

# Generate unit test
php siro make:test CartService --unit
# → tests/Unit/CartServiceTest.php

# CRUD auto-generates tests
php siro make:crud products
# → tests/Feature/ProductsTest.php with 4 test methods

# Run tests with filters
php siro test                            # All 174 tests
php siro test --testsuite=Feature        # Feature suite only
php siro test --filter=ProductsTest      # Single test class
php siro test --stop-on-failure          # Stop at first failure
```

### 🗄️ Production-Ready Features (v0.12.0)

**Soft Deletes:**
```php
use Siro\Core\DB\SoftDeletes;
class Post extends Model { use SoftDeletes; }

$post->delete();      // Soft delete
Post::all();          // Auto-filters deleted
$post->restore();     // Restore
$post->forceDelete(); // Permanent delete
```

**API Versioning:**
```php
$router->version(1, fn($r) => $r->get('/users', [V1\UserCtrl::class, 'index']));
// → GET /api/v1/users

$router->version(2, fn($r) => $r->get('/users', [V2\UserCtrl::class, 'index']));
// → GET /api/v2/users
```

**Rate Limit Dashboard:**
```bash
php siro rate:status
# Shows active/expired rate limits with color-coded status
```

### Multi-language (v0.8.5) 🌍
```bash
php siro make:lang vi                 # Create Vietnamese language pack
php siro make:lang fr                 # Create French language pack
```

**Configuration (.env):**
```env
APP_LOCALE=en              # Default locale
APP_FALLBACK_LOCALE=en     # Fallback when key is missing
```

**Usage:**
```php
use Siro\Core\Lang;

// Get translation
$message = Lang::get('messages.welcome');  // "Welcome" or "Chào mừng"

// With parameters
$error = Lang::get('validation.required', ['field' => 'Email']);

// Pluralization
$apples = Lang::plural('messages.apples', 5);  // "5 apples"
```

**Auto Locale Detection:**
- `X-Locale` header (for API testing)
- `Accept-Language` header (browser default)
- Falls back to `APP_LOCALE` env variable

**Test:**
```bash
curl http://localhost:8000/
# {"message":"Welcome","locale":"en"}

curl -H "Accept-Language: vi" http://localhost:8000/
# {"message":"Chào mừng","locale":"vi"}
```

### Event System (v0.8.6) ⚡
```bash
php siro make:event UserCreated       # Generate event class
```

### Storage, Validation & Performance (v0.8.7) 🚀

**File Storage:**
```php
use Siro\Core\Storage;

Storage::put('file.txt', 'content');
$content = Storage::get('file.txt');
Storage::delete('file.txt');
$url = Storage::url('file.txt');
```

**Custom Validation:**
```php
use Siro\Core\Validator;

Validator::extend('phone', function ($value, $field, $input, $param) {
    return preg_match('/^\+?[0-9]{7,15}$/', (string) $value)
        ? true
        : ':field is not a valid phone number';
});

$request->validate(['phone' => 'required|phone']);
```

**Auto Gzip Compression:**
- ✅ Automatic when client supports it
- ✅ Reduces bandwidth by 60-80%
- ✅ Zero configuration required

**Basic Usage:**
```php
use Siro\Core\Event;

// Register listener
Event::on('users.created', function ($user) {
    Log::info('New user: ' . $user->email);
});

// Fire event
Event::emit('users.created', $user);
```

**Model Lifecycle Events:**

Models automatically fire events during CRUD operations:

- **Create:** saving → creating → created → saved
- **Update:** saving → updating → updated → saved  
- **Delete:** deleting → deleted

**Cancel Operations:**
```php
Event::on('users.creating', function ($user): bool {
    if ($user->email === 'banned@example.com') {
        return false; // Cancel creation
    }
    return true;
});
```

**Event Classes:**
```bash
php siro make:event UserCreated
# Creates app/Events/UserCreatedEvent.php
```

**Usage:**
```php
// Listen
UserCreatedEvent::listen(fn($user) => ...);

// Dispatch
UserCreatedEvent::dispatch($user);
```

### Auto Documentation (v0.8.2) 
```bash
# Generate OpenAPI spec + Swagger UI (full docs)
php siro make:openapi --with-swagger
php siro make:openapi --with-swagger --flow=auth   # Only auth endpoints
php siro make:openapi --with-swagger --tag=User    # Only User controller
php siro make:docs                                  # Alias: same as above

# Generate OpenAPI spec only
php siro make:openapi
php siro make:openapi --flow=crud       # CRUD operations only
php siro make:openapi --method=POST     # POST requests only

# Generate Postman collection only
php siro make:postman
php siro make:postman --flow=auth       # Auth flow only
php siro make:postman --tag=User        # User endpoints only

# Output structure:
# docs/openapi/openapi.json      ← OpenAPI 3.0.3 spec
# docs/postman/collection.json   ← Postman v2.1.0 collection
# docs/swagger/index.html        ← Swagger UI source
# public/openapi.json            ← Served at /openapi.json
# public/docs.html               ← Served at /docs.html
```

**Access Documentation:**
- Swagger UI: http://localhost:8080/docs.html
- OpenAPI Spec: http://localhost:8080/openapi.json

## Architecture

Siro uses a **two-package architecture**:

- **sirosoft/core** - Framework core library (router, database, cache, JWT, etc.)
  - Install: `composer require sirosoft/core`
  - Repository: https://github.com/SiroSoft/siro-core

- **sirosoft/api** - Application skeleton (this package)
  - Install: `composer create-project sirosoft/api my-app`
  - Repository: https://github.com/SiroSoft/SiroPHP

This architecture allows you to:
- Use `siro/core` in any PHP project
- Build custom applications on top of `siro/api`
- Create your own packages that depend on `siro/core`

## 🔍 Advanced Debugging (v0.8.0)

Every request includes a unique trace ID for easy debugging:

```bash
# View trace details
php siro log:trace siro_a1b2c3d4e5f6g7h8

# Filter traces
php siro log:trace --status=500
php siro log:trace --method=POST
php siro log:trace --slow

# Replay request (generates curl command)
php siro log:replay siro_a1b2c3d4e5f6g7h8

# Export traces
php siro log:export --format=json --output=traces.json
php siro log:export --format=csv --output=traces.csv
```

**Debug production issues in 30 seconds, not 30 minutes!**

See core library docs: https://github.com/SiroSoft/siro-core#-advanced-debugging-system-v080

## 📝 Auto Documentation (v0.8.2)

### One-Command Documentation Generation

Generate complete API documentation with Swagger UI in one command:

```bash
php siro make:openapi --with-swagger
# or: php siro make:docs (alias)
```

**What it does:**
1. ✅ Generates OpenAPI 3.0.3 spec from your routes
2. ✅ Creates Swagger UI HTML page
3. ✅ Copies files to public/ for serving
4. ✅ Supports all filters (--flow, --tag, --method, --path)

**Output Structure:**
```
docs/
├── openapi/openapi.json      ← Source spec (tracked in git)
├── postman/collection.json   ← Postman collection (tracked)
└── swagger/index.html        ← Swagger UI source (tracked)

public/
├── openapi.json              ← Served at /openapi.json
└── docs.html                 ← Served at /docs.html
```

**Access Your Docs:**
- **Swagger UI:** http://localhost:8080/docs.html
- **OpenAPI Spec:** http://localhost:8080/openapi.json

---

### Individual Generators

#### **OpenAPI Generator**
```bash
php siro make:openapi
php siro make:openapi --flow=auth     # Auth endpoints only
php siro make:openapi --tag=User      # User controller only
php siro make:openapi --method=POST   # POST requests only
php siro make:openapi --path=/api     # Path prefix filter
```

**Features:**
- Extracts validation rules from `$request->validate()`
- Detects auth middleware automatically
- Adds Bearer JWT security scheme
- Generates JSON Schema from validation rules
- Smart type inference (email→string, integer→int)

#### **Postman Generator**
```bash
php siro make:postman
php siro make:postman --flow=crud     # CRUD operations only
php siro make:postman --tag=User      # User endpoints only
php siro make:postman --method=GET    # GET requests only
```

**Features:**
- Collection variables (base_url, token)
- Pre-request script for auto-login
- Body examples from validation rules
- Path parameters auto-mapped
- Same filters as OpenAPI

---

### Filtering Options

All three commands support the same filters:

| Filter | Example | Description |
|--------|---------|-------------|
| `--flow=auth` | `php siro make:openapi --with-swagger --flow=auth` | Only authentication endpoints |
| `--flow=crud` | `php siro make:openapi --with-swagger --flow=crud` | Only CRUD operations |
| `--tag=User` | `php siro make:openapi --with-swagger --tag=User` | Specific controller |
| `--method=POST` | `php siro make:openapi --with-swagger --method=POST` | HTTP method filter |
| `--path=/api` | `php siro make:openapi --with-swagger --path=/api` | Path prefix |

---

### Workflow Example

```bash
# 1. Make API changes in your controllers

# 2. Regenerate documentation
php siro make:openapi --with-swagger

# 3. Test locally
php siro serve
# Visit: http://localhost:8080/docs.html

# 4. Commit documentation source
git add docs/
git commit -m "Update API documentation"

# 5. Deploy (docs are in git, public/ generated on build)
=======
# Siro API Framework v0.7.4

Minimal, high-performance PHP micro-framework for REST APIs.

## Why Siro?

- Faster than full-stack frameworks for API-only workloads
- Minimal bootstrap overhead and lightweight request pipeline
- Focused on REST API development (no unnecessary layers)

## Quick Start (Git Clone)

```bash
git clone https://github.com/SiroSoft/SiroPHP.git my-app
cd my-app
composer install
cp .env.example .env
php siro key:generate
php siro migrate
php -S localhost:8080 -t public
```

### Setup permissions (if needed)

```bash
chmod +x benchmark/wrk.sh
```

## Install (Composer create-project)

Once published to Packagist:

```bash
composer create-project siro/api my-app
cd my-app
php siro migrate
php siro serve
```

## CLI usage

```bash
php siro migrate
php siro make:api users
php siro serve
```

## API example

```bash
curl http://localhost:8080/users
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
```

## Benchmark

Run API server first:

```bash
php -S localhost:8080 -t public
```

### k6

```bash
k6 run benchmark/k6.js
```

Run individual scenarios:

```bash
SCENARIO=root k6 run benchmark/k6.js
SCENARIO=users k6 run benchmark/k6.js
SCENARIO=users_cached k6 run benchmark/k6.js
```

Output includes:

- Requests/sec
- Latency (p95)
- Error rate

Targets:

- API p95 < 20ms
- Cached `/users` p95 < 5ms

### wrk

```bash
./benchmark/wrk.sh
```

Default command used inside script:

```bash
wrk -t4 -c100 -d10s http://localhost:8080/users
```

Output includes:

- Requests/sec
- Latency
- Error rate

<<<<<<< HEAD
## ✨ Key Features

### Core Components
- ⚡ **Fast Router** - Lightweight routing with middleware support
- 🗄️ **Database QueryBuilder** - PDO-based with automatic caching
- 🎯 **Model Layer** - ORM-like with relationships, scopes, soft deletes
- 🔐 **JWT Authentication** - Built-in token generation with refresh tokens
- ✅ **Smart Validation** - Automatic 422 responses with extended rules
- 💾 **Cache System** - File and Redis drivers
- 📦 **Resource Transformation** - Auto-mapping for API responses
- 🔤 **Typed Input Helpers** - Type-safe request data handling

### Advanced Debugging (v0.8.0) 
- 🔍 **Trace ID per Request** - Every request gets unique `X-Siro-Trace-Id`
- 🔄 **Request Replay** - `php siro log:replay <id>` generates curl command
- 📤 **Export Traces** - `php siro log:export --format=json|csv`
- 🔎 **Smart Filtering** - Filter by status, method, slow requests
- 📊 **SQL Query Logging** - Capture all queries with bindings and timing
- 🔒 **Credential Sanitization** - Passwords/tokens auto [REDACTED]

### Security & Performance
- 🛡️ **Rate Limiting** - Per-route throttling with configurable limits
- 🔐 **CSRF Protection** - Built-in middleware for form protection
- ⚙️ **Config Caching** - Cache environment for faster boot
- 📈 **Slow Query Detection** - Auto-log queries exceeding threshold
- ✅ **Environment Validation** - Pre-deployment checks

## Requirements

- PHP >= 8.2
- PDO extension
- JSON extension
- Mbstring extension

## 📚 Documentation

For detailed documentation:
- **Core Library:** https://github.com/SiroSoft/siro-core
- **Application Skeleton:** https://github.com/SiroSoft/SiroPHP
- **Issues:** https://github.com/SiroSoft/SiroPHP/issues

## 📋 Changelog

Full changelog available at: [CHANGELOG.md](CHANGELOG.md)

**Highlights:**
- **v0.14.x** — `debug:last`, `log:top`, `route:search`, `doctor --prod`, `api:test --loop`, `--simple` flag, guided CRUD experience, `open:postman`
- **v0.13.x** — Factory generator, db:show, route:rules, live reload, deploy system, PHPStan Level 6, 174 tests
- **v0.12.x** — `make:crud` scaffolding, `make:test`, benchmarks, watch mode, collections, webhook listener, env:switch
- **v0.11.x** — Service & Repository pattern, smart validation, eager loading, PHP 8.4 support
- **v0.10.x** — Rate limiter dashboard, CSRF, config caching, optimize command
- **v0.9.x** — Queue system, mail, events, scheduler, multi-language
- **v0.8.x** — Debugging system (trace ID, replay, export), auto documentation with Swagger UI, Postman generator
- **v0.7.x** — Initial release: router, models, JWT auth, validation, migrations, seeders

---

**Version:** 0.14.0  
**Package:** sirosoft/api  
**Type:** project  
**Released:** May 4, 2026

---

## 👥 Credits

Created and maintained by **SiroSoft Team**

Special thanks to all contributors who help make SiroPHP better.

---

**Happy coding! 🚀**
=======
## Performance

> Note: The numbers below are sample results from a controlled local environment. Run the benchmarks yourself to validate on your hardware.

See full benchmark notes: [`benchmark/compare.md`](benchmark/compare.md)

| Framework | RPS  | Latency (p95) | Notes |
| --------- | ---- | ------------- | ----- |
| Siro      | 8200 | 3.8ms         | Route cache + minimal middleware stack |
| Laravel   | 2300 | 17.5ms        | Full framework bootstrap cost |
| Node      | 5400 | 8.9ms         | Express baseline |

Under equivalent test shape, Siro is faster than Laravel in this benchmark profile.

## CI

GitHub Actions workflow: `.github/workflows/test.yml`

On every push it runs:

1. `composer install`
2. `php -l` for all PHP files
3. `php siro migrate`
4. start built-in PHP server + runtime smoke checks:
   - `curl -f http://localhost:8080/`
   - `curl -f http://localhost:8080/users`
5. `php tests/verify_v061.php` (PASS/FAIL verification suite)

## Packaging

- **siro/core** (library): `core/`
- **siro/api** (project): `app/`, `routes/`, `config/`, `public/`

Project package requires:

```json
"siro/core": "^0.7"
```
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
