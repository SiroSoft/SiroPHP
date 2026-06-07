---
title: M od el
description: SiroPHP M od el reference
sidebar_position: 18
sidebar_label: M od el
---

# Model API Reference

## Overview

The Model layer provides an ORM-like interface for database operations with relationships, scopes, and soft deletes.

---

## Basic Usage

### Define a Model

```php
<?php
namespace App\Models;

use Siro\Core\Model;

final class User extends Model
{
    protected string $table = 'users';
    
    protected array $fillable = ['name', 'email', 'password'];
    
    protected array $hidden = ['password'];
    
    protected array $casts = [
        'id' => 'int',
        'status' => 'int',
        'email_verified_at' => 'datetime',
    ];
}
```

### CRUD Operations

```php
// Create
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('secret'),
]);

// Find by ID
$user = User::find(1);

// Find or fail
$user = User::findOrFail(1); // Throws exception if not found

// Update
$user->update(['name' => 'Jane Doe']);

// Delete
$user->delete();

// All records
$users = User::all();

// Count
$count = User::count();
```

---

## Query Builder Integration

Models inherit all QueryBuilder methods:

```php
// Where clauses
$users = User::where('status', 'active')->get();
$users = User::where('age', '>=', 18)->where('status', 'active')->get();

// Ordering
$users = User::orderBy('created_at', 'desc')->get();

// Limiting
$users = User::limit(10)->get();

// Select specific columns
$users = User::select(['id', 'name', 'email'])->get();

// Pagination
$result = User::paginate(20, $page);
// Returns: ['data' => [...], 'meta' => ['page' => 1, 'per_page' => 20, ...]]

// First record
$user = User::where('email', 'john@example.com')->first();

// Pluck single column
$emails = User::pluck('email');

// Chunk large datasets
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        // Process each user
    }
});
```

---

## Relationships

### HasOne (One-to-One)

```php
// User model
public function profile(): HasOne
{
    return $this->hasOne(Profile::class, 'user_id', 'id');
}

// Usage
$user = User::find(1);
$profile = $user->profile; // Profile instance or null
```

### HasMany (One-to-Many)

```php
// User model
public function posts(): HasMany
{
    return $this->hasMany(Post::class, 'user_id', 'id');
}

// Usage
$user = User::find(1);
$posts = $user->posts; // Collection of posts

// Query relationship
$publishedPosts = $user->posts()->where('status', 'published')->get();
```

### BelongsTo (Many-to-One)

```php
// Post model
public function author(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id', 'id');
}

// Usage
$post = Post::find(1);
$author = $post->author; // User instance
```

### BelongsToMany (Many-to-Many)

```php
// Post model
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id');
}

// Usage
$post = Post::find(1);
$tags = $post->tags; // Collection of tags

// Attach relationship
$post->tags()->attach($tagId);
$post->tags()->attach([$tagId1, $tagId2]);

// Detach relationship
$post->tags()->detach($tagId);
$post->tags()->detach([$tagId1, $tagId2]);

// Sync (replace all)
$post->tags()->sync([$tagId1, $tagId2]);

// Toggle
$post->tags()->toggle($tagId);

// Check if has relationship
if ($post->tags()->has($tagId)) {
    // Post has this tag
}
```

#### Pivot Data (Extra Columns)

Retrieve extra pivot columns using `withPivot()`:

```php
// Define relationship with pivot columns
public function orders(): BelongsToMany
{
    return $this->belongsToMany(Product::class, 'order_product')
        ->withPivot(['quantity', 'price']);
}

// Pivot columns are included in query results
$order->products; // Each product has quantity and price from pivot

// Attach with pivot data
$user->roles()->attach($roleId, ['assigned_by' => $userId]);

// Sync with pivot data (associative array)
$user->roles()->sync([
    $roleId1 => ['assigned_by' => $userId],
    $roleId2 => ['assigned_by' => $userId],
]);
```

### MorphMany (Polymorphic One-to-Many)

One model can belong to multiple other models on a single association.

```php
// Comment belongs to Post OR Product OR Article
// Table: comments (id, body, commentable_type, commentable_id)

class Comment extends Model
{
    // Inverse polymorphic: which model owns this comment?
    public function commentable(): MorphTo
    {
        return $this->morphTo('commentable');
    }
}

class Post extends Model
{
    // A post has many comments
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Product extends Model
{
    // A product also has many comments
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

// Usage: get comments on any model
$post->comments;     // All comments on this post
$product->comments;  // All comments on this product

// Usage: get the parent of a comment
$comment->commentable; // Returns Post or Product or Article

// Create via polymorphic relationship
$post->comments()->create(['body' => 'Great post!']);
$product->comments()->create(['body' => 'Nice product!']);
```

### MorphTo (Inverse Polymorphic)

Defined automatically by `morphTo()` inside the child model:

```php
$comment = Comment::find(1);
$owner = $comment->commentable; // Post, Product, or Article
```

Eager loading works for both directions:

```php
// Eager load morphMany
$post = Post::with('comments')->find(1);

// Eager load morphTo (inverse)
$comment = Comment::with('commentable')->find(1);
```

---

## Eager Loading

Prevent N+1 query problems:

```php
// ❌ Bad - N+1 queries
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name; // Query per post!
}

// ✅ Good - 2 queries total
$posts = Post::with('author')->get();
foreach ($posts as $post) {
    echo $post->author->name; // No additional queries
}

// Multiple relationships
$posts = Post::with('author', 'comments', 'tags')->get();

// Nested relationships
$posts = Post::with('author.profile', 'comments.user')->get();

// Conditional eager loading
$posts = Post::with(['comments' => function ($query) {
    $query->where('status', 'approved')->orderBy('created_at', 'desc');
}])->get();
```

---

## Soft Deletes

Enable soft deletes to keep deleted records in database:

```php
<?php
namespace App\Models;

use Siro\Core\Model;
use Siro\Core\DB\SoftDeletes;

final class Post extends Model
{
    use SoftDeletes;
    
    protected string $table = 'posts';
}
```

**Usage:**

```php
// Soft delete (sets deleted_at timestamp)
$post->delete();

// Query automatically excludes soft-deleted records
Post::all(); // Only non-deleted posts

// Include soft-deleted in query
Post::withTrashed()->get();

// Get only soft-deleted records
Post::onlyTrashed()->get();

// Restore a soft-deleted record
Post::withTrashed()->find(1)->restore();

// Permanently delete from database
$post->forceDelete();

// Check if record is soft-deleted
if ($post->trashed()) {
    echo "This post was deleted";
}
```

**Migration for soft deletes:**

```php
Schema::table('posts', function (Blueprint $table) {
    $table->softDeletes(); // Adds deleted_at column
});
```

---

## Attribute Casting

Automatically cast attributes to native types:

```php
protected array $casts = [
    'id' => 'int',
    'price' => 'float',
    'is_active' => 'bool',
    'metadata' => 'array',
    'published_at' => 'datetime',
    'options' => 'json',
];

// Usage
$user = User::find(1);
$user->is_active; // true (boolean, not string "1")
$user->metadata; // ['key' => 'value'] (array, not JSON string)
$user->published_at; // DateTime instance
```

**Available cast types:**
- `int`, `integer`
- `real`, `float`, `double`
- `string`
- `bool`, `boolean`
- `array`
- `json`
- `object`
- `datetime`, `date`
- `timestamp`

---

## Accessors & Mutators (v0.28+)

Transform attributes automatically when getting or setting values.

### Accessors

Accessors are called when you retrieve an attribute. Define a method with the pattern `get{AttributeName}Attribute`:

```php
class User extends Model
{
    // Accessor - automatically called when accessing $user->name
    public function getNameAttribute(mixed $value): string
    {
        return ucfirst(strtolower((string) $value));
    }
    
    // Virtual accessor (no database column)
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

$user = User::find(1);
echo $user->name;      // Auto-formatted: "John Doe"
echo $user->full_name; // Virtual: "John Doe"
```

### Mutators

Mutators are called when you set an attribute. Define a method with the pattern `set{AttributeName}Attribute`:

```php
class User extends Model
{
    // Mutator - automatically called when setting $user->email
    public function setEmailAttribute(string $value): void
    {
        // IMPORTANT: Set directly to avoid infinite recursion
        $reflection = new \ReflectionClass($this);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $attrs = $property->getValue($this);
        $attrs['email'] = strtolower($value);
        $property->setValue($this, $attrs);
    }
    
    // Hash password automatically
    public function setPasswordAttribute(string $value): void
    {
        $reflection = new \ReflectionClass($this);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $attrs = $property->getValue($this);
        $attrs['password'] = password_hash($value, PASSWORD_BCRYPT);
        $property->setValue($this, $attrs);
    }
}

$user = new User();
$user->email = 'TEST@EXAMPLE.COM';  // Stored as 'test@example.com'
$user->password = 'secret123';       // Stored as hashed value
```

**⚠️ Important:** Mutators must NOT call `$this->setAttribute()` inside the mutator method, as this causes infinite recursion. Use reflection or a helper trait to set attributes directly.

### Helper Trait for Cleaner Mutators

Create a reusable trait to simplify mutator syntax:

```php
// app/Traits/MutatorHelper.php
trait MutatorHelper
{
    protected function setRawAttribute(string $key, mixed $value): void
    {
        $reflection = new \ReflectionClass($this);
        $property = $reflection->getProperty('attributes');
        $property->setAccessible(true);
        $attrs = $property->getValue($this);
        $attrs[$key] = $value;
        $property->setValue($this, $attrs);
    }
}

// Usage in Model
class User extends Model
{
    use MutatorHelper;
    
    public function setEmailAttribute(string $value): void
    {
        $this->setRawAttribute('email', strtolower($value));
    }
}
```

---

## Virtual Attributes with Appends (v0.28+)

Add computed/virtual attributes to JSON and array serialization using the `$appends` property:

```php
class User extends Model
{
    protected array $appends = ['full_name', 'initials', 'age'];
    
    // These accessors will be included in toArray() and json_encode()
    public function getFullNameAttribute(): string
    {
        return ($this->first_name ?? '') . ' ' . ($this->last_name ?? '');
    }
    
    public function getInitialsAttribute(): string
    {
        $first = $this->first_name ?? '';
        $last = $this->last_name ?? '';
        return strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
    }
    
    public function getAgeAttribute(): int
    {
        return date_diff(date_create($this->birth_date), date_create('now'))->y;
    }
}

$user = User::find(1);
$data = $user->toArray();
// Includes: id, first_name, last_name, birth_date, full_name, initials, age

$json = json_encode($user);
// {"id":1,"first_name":"John","last_name":"Doe","full_name":"John Doe","initials":"JD","age":30}
```

**Note:** Appended attributes respect the `$hidden` property. If an appended attribute is in `$hidden`, it will not be included.

```php
class User extends Model
{
    protected array $hidden = ['secret_data'];
    protected array $appends = ['secret_data']; // Will NOT appear in output
}
```

---

## DateTime Auto-Formatting (v0.28+)

DateTime casts now automatically format to strings for JSON-safe serialization. This eliminates common JSON encoding errors with DateTime objects.

```php
class Post extends Model
{
    protected array $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'published_at' => 'date',
    ];
}

$post = Post::find(1);

// Before v0.28: Returns DateTime object (causes JSON errors)
// After v0.28: Returns formatted string
echo $post->created_at;   // "2024-01-15 10:30:00"
echo $post->published_at; // "2024-01-15 00:00:00"

// JSON serialization works perfectly - no more errors!
$json = json_encode($post);
// {"id":1,"created_at":"2024-01-15 10:30:00","published_at":"2024-01-15 00:00:00"}

// In API responses
return Response::success($post); // DateTime fields are strings, not objects
```

**Supported date cast types:**
- `'datetime'` - Full datetime with time: `Y-m-d H:i:s`
- `'date'` - Date only: `Y-m-d H:i:s`

---

## Scopes

Reusable query constraints:

```php
// Scope method (prefix with "scope")
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopePopular($query)
{
    return $query->where('views', '>', 1000);
}

// Usage
$users = User::active()->get();
$posts = Post::popular()->orderBy('created_at', 'desc')->get();

// Dynamic scopes
public function scopeOfType($query, string $type)
{
    return $query->where('type', $type);
}

// Usage
$posts = Post::ofType('article')->get();
```

---

## Mass Assignment Protection

Models require explicit `$fillable` declaration:

```php
final class User extends Model
{
    // Only these fields can be mass-assigned
    protected array $fillable = ['name', 'email'];
    
    // These are protected automatically:
    // - password
    // - role
    // - is_admin
}

// ❌ Will trigger warning and block unauthorized fields
User::create($request->all());

// ✅ Explicitly allow only safe fields
User::create($request->only(['name', 'email']));
```

**Runtime Warning:**
If `$fillable` is empty, framework triggers `E_USER_WARNING`:
```
Mass assignment protection: $fillable is empty on User model.
No fields will be mass-assigned. Define $fillable array.
```

---

## Hidden Attributes

Hide sensitive attributes from JSON responses:

```php
protected array $hidden = ['password', 'remember_token'];

// Usage
return Response::json($user);
// Output: {"id":1,"name":"John","email":"john@example.com"}
// Password is NOT included
```

**Temporarily show hidden attributes:**

```php
return Response::json($user->makeVisible(['password']));
```

---

## Timestamps

Models automatically manage `created_at` and `updated_at`:

```php
// Disable timestamps
public bool $timestamps = false;

// Custom timestamp columns
const CREATED_AT = 'creation_date';
const UPDATED_AT = 'last_update';
```

---

## Events

Models fire lifecycle events automatically:

```php
// Listen to events
Event::on('users.creating', function ($user): bool {
    // Validate before create
    if (User::where('email', $user->email)->exists()) {
        return false; // Cancel creation
    }
    return true;
});

Event::on('users.created', function ($user) {
    // Send welcome email
    Mail::to($user->email)
        ->subject('Welcome!')
        ->html('<h1>Welcome!</h1>')
        ->queue();
});
```

**Model lifecycle events:**
```
saving → creating → INSERT → created → saved
saving → updating → UPDATE → updated → saved
deleting → DELETE → deleted
```

---

## Best Practices

### 1. Always Use Eager Loading

```php
// ❌ Bad
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;
}

// ✅ Good
$posts = Post::with('author')->get();
foreach ($posts as $post) {
    echo $post->author->name;
}
```

### 2. Use Fillable Protection

```php
// ❌ Dangerous
protected array $fillable = []; // Allows all fields

// ✅ Safe
protected array $fillable = ['name', 'email'];
```

### 3. Hide Sensitive Data

```php
protected array $hidden = ['password', 'token', 'secret'];
```

### 4. Cast Attributes Properly

```php
protected array $casts = [
    'is_active' => 'bool',
    'metadata' => 'array',
    'published_at' => 'datetime',
];
```

### 5. Use Scopes for Reusable Queries

```php
// Define scope
public function scopePublished($query)
{
    return $query->where('status', 'published');
}

// Use scope
$posts = Post::published()->orderBy('created_at', 'desc')->get();
```

### 6. Add Database Indexes

```php
Schema::table('users', function (Blueprint $table) {
    $table->index('email'); // Speed up WHERE email = ?
    $table->index(['status', 'created_at']); // Composite index
});
```

---

## Examples

### Blog Post Model

```php
<?php
namespace App\Models;

use Siro\Core\Model;
use Siro\Core\DB\SoftDeletes;

final class Post extends Model
{
    use SoftDeletes;
    
    protected string $table = 'posts';
    
    protected array $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'user_id',
    ];
    
    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'published_at' => 'datetime',
    ];
    
    protected array $hidden = [];
    
    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }
    
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id');
    }
    
    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
    
    public function scopePopular($query)
    {
        return $query->where('views', '>', 1000);
    }
    
    // Accessors
    public function getExcerptAttribute(): string
    {
        return substr($this->content, 0, 200) . '...';
    }
}
```

**Usage:**

```php
// Get published posts with author and tags
$posts = Post::published()
    ->with('author', 'tags')
    ->orderBy('published_at', 'desc')
    ->paginate(20);

// Create new post
$post = Post::create([
    'title' => 'My First Post',
    'slug' => 'my-first-post',
    'content' => 'Content here...',
    'status' => 'draft',
    'user_id' => $request->user()['id'],
]);

// Publish post
$post->update(['status' => 'published', 'published_at' => date('Y-m-d H:i:s')]);

// Add tags
$post->tags()->attach([1, 2, 3]);
```

---

## Troubleshooting

### Problem: "Mass assignment protection" warning

**Solution:**
Define `$fillable` array in your model:
```php
protected array $fillable = ['field1', 'field2'];
```

### Problem: Relationship returns null

**Check:**
1. Foreign key column exists in database
2. Related record exists
3. Relationship method name is correct
4. Use eager loading: `Model::with('relation')->get()`

### Problem: N+1 queries

**Solution:**
Use eager loading:
```php
// Instead of
$posts = Post::all();

// Use
$posts = Post::with('author', 'comments')->get();
```

---

## See Also

- [Database Guide](../guides/DATABASE.md) - Query builder reference
- [SoftDeletes API](SoftDeletes.md) - Soft delete support
- [Pagination API](Pagination.md) - Pagination methods
