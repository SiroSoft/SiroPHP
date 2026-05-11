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
    'password' => bcrypt('secret'),
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

## Accessors & Mutators

Transform attributes when getting/setting:

```php
// Accessor (get attribute)
public function getFullNameAttribute(): string
{
    return "{$this->first_name} {$this->last_name}";
}

// Usage
$user->full_name; // "John Doe"

// Mutator (set attribute)
public function setPasswordAttribute(string $value): void
{
    $this->attributes['password'] = bcrypt($value);
}

// Usage
$user->password = 'secret'; // Automatically hashed
```

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
    'user_id' => auth()->id(),
]);

// Publish post
$post->update(['status' => 'published', 'published_at' => now()]);

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

- [Database API](Database.md) - Query builder reference
- [Relationships Guide](../guides/RELATIONSHIPS.md) - Detailed relationship examples
- [Eloquent vs SiroPHP Models](../guides/MIGRATION_FROM_LARAVEL.md) - Comparison guide
