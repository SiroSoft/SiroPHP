# Database Guide

## Configuration

Database settings are defined in `.env` and loaded via `config/database.php`.

```env
# SQLite (development/testing)
DB_CONNECTION=sqlite
DB_DATABASE=storage/database.sqlite

# MySQL (production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siro
DB_USERNAME=root
DB_PASSWORD=

# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=siro
DB_USERNAME=postgres
DB_PASSWORD=

# Optional
DB_CHARSET=utf8mb4
DB_SLOW_QUERY_THRESHOLD=100  # ms
```

Config file loads driver with auto-detected default ports (MySQL=3306, PostgreSQL=5432, SQLite=0).

## Migrations

Migrations go in `database/migrations/` with timestamp-prefixed filenames.

```php
<?php
use Siro\Core\Schema;
use Siro\Core\DB\Blueprint;

return new class {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('name', 200);
            $t->text('description')->nullable();
            $t->decimal('price', 10, 2)->default(0);
            $t->integer('stock')->default(0);
            $t->string('category', 100)->nullable();
            $t->string('status', 20)->default('active');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('products');
    }
};
```

### Blueprint Column Types

| Method | Description |
|--------|-------------|
| `$t->id()` | Auto-increment primary key |
| `$t->string('col', length)` | VARCHAR column |
| `$t->text('col')` | TEXT column |
| `$t->integer('col')` | INT column |
| `$t->smallint('col')` | SMALLINT column |
| `$t->decimal('col', precision, scale)` | DECIMAL column |
| `$t->timestamps()` | Adds created_at, updated_at |

### Column Modifiers

```php
$t->string('email')->unique();
$t->text('bio')->nullable();
$t->integer('views')->default(0);
```

### Running Migrations

```bash
php siro migrate             # Run pending migrations
php siro migrate:rollback    # Rollback last batch
php siro migrate:status      # Show migration status
php siro db:show users       # Inspect table structure
```

## Query Builder

Access via `DB::table()` or the `Database` class.

```php
use Siro\Core\DB;
use Siro\Core\Database;

// Raw queries with PDO
$rows = Database::select('SELECT * FROM users WHERE status = :status', ['status' => 1]);

$affected = Database::execute(
    'UPDATE users SET name = :name WHERE id = :id',
    ['name' => 'John', 'id' => 1]
);

// Fetch single row
$user = Database::first('SELECT * FROM users WHERE email = :email', ['email' => 'a@b.com']);

// Transactions
Database::transaction(function () {
    Database::execute('INSERT INTO logs ...');
    Database::execute('UPDATE users ...');
});
// Auto-rolls back on exception

// Query Builder
$users = DB::table('users')
    ->where('status', '=', 1)
    ->where('role', '=', 'admin')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Joins
$posts = DB::table('posts')
    ->join('users', 'posts.user_id', '=', 'users.id')
    ->where('posts.status', '=', 'published')
    ->get(['posts.*', 'users.name as author']);

// Pagination
$results = DB::table('products')
    ->where('status', '=', 'active')
    ->paginate(perPage: 15, page: 1);
// Returns items + meta {page, per_page, total, last_page}

// Cursor pagination
$cursor = DB::table('users')->orderBy('id')->cursor();
foreach ($cursor as $row) {
    // Process one row at a time (memory efficient)
}
```

## Model ORM

Models extend `Siro\Core\Model` and map to database tables.

```php
use Siro\Core\Model;

final class Product extends Model
{
    protected string $table = 'products';

    protected array $fillable = ['name', 'price', 'stock', 'category', 'status'];

    protected array $hidden = ['internal_code'];

    protected array $casts = [
        'id' => 'int',
        'price' => 'float',
        'stock' => 'int',
    ];
}
```

### CRUD Operations

```php
// Create
$product = Product::create([
    'name' => 'Laptop',
    'price' => 1500.00,
    'stock' => 100,
]);

// Read
$product = Product::find(1);
$products = Product::where('status', '=', 'active')->get();
$first = Product::where('sku', '=', 'LAP-001')->first();

// Update
$product = Product::find(1);
$product->name = 'Updated Laptop';
$product->save();

// Delete
$product = Product::find(1);
$product->delete();
```

### Relationships

```php
class User extends Model
{
    protected string $table = 'users';

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

class Post extends Model
{
    protected string $table = 'posts';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

### Eager Loading

```php
// Load posts for all users in 2 queries (N+1 prevention)
$users = User::with('posts')->where('status', '=', 1)->get();

foreach ($users as $user) {
    // $user->posts is already loaded
}
```

### Soft Deletes

Soft delete support is built in — add a `deleted_at` column to your table:

```php
Schema::create('users', function (Blueprint $t) {
    $t->id();
    // ... other columns
    $t->datetime('deleted_at')->nullable();
    $t->timestamps();
});
```

Models automatically exclude soft-deleted records and provide:

```php
// Include soft-deleted
$users = User::withTrashed()->get();

// Only soft-deleted
$trashed = User::onlyTrashed()->get();

// Restore
$user = User::withTrashed()->find(1);
$user->restore();

// Force delete
$user->forceDelete();
```

## Seeding and Factories

### Factories

```bash
php siro make:factory User
```

```php
final class UserFactory
{
    public static function new(): self
    {
        return new self();
    }

    public function count(int $count): self
    {
        $this->count = max(1, $count);
        return $this;
    }

    public function with(array $data): self
    {
        $this->overrides = $data;
        return $this;
    }

    public function definition(): array
    {
        return [
            'name' => 'User_' . bin2hex(random_bytes(4)),
            'email' => 'user_' . bin2hex(random_bytes(4)) . '@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function create(): User|array
    {
        return User::create(array_merge($this->definition(), $this->overrides));
    }
}

// Usage
$user = UserFactory::new()->create();
$users = UserFactory::new()->count(10)->create();
$admin = UserFactory::new()->with(['role' => 'admin'])->create();
```

### Seeders

```bash
php siro db:seed
```

```php
// database/seeds/DatabaseSeeder.php
final class DatabaseSeeder
{
    public array $calls = [
        UserSeeder::class,
    ];

    public function run(): void
    {
        foreach ($this->calls as $class) {
            $seeder = new $class();
            $seeder->run();
        }
    }
}
```

## Connection Management

```php
// Get raw PDO connection
$pdo = Database::connection();

// Get driver name
$driver = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
// Returns 'mysql', 'pgsql', 'sqlite'
```

## Best Practices

- Use SQLite for development/testing, MySQL/PostgreSQL for production.
- Always use parameterized queries with named placeholders (`:param`).
- Wrap bulk operations in `Database::transaction()` for atomicity.
- Use eager loading to avoid N+1 query problems.
- Set `DB_SLOW_QUERY_THRESHOLD` to identify slow queries in logs.
- Keep migrations immutable once deployed — create new migrations to alter tables.
