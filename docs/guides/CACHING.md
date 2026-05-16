# Caching Guide

## Configuration

Cache settings in `.env`:

```env
CACHE_DRIVER=file         # file or redis
CACHE_TTL=60              # Default TTL in seconds
CACHE_PREFIX=siro:        # Key prefix

# Redis settings (when CACHE_DRIVER=redis)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_TIMEOUT=0.2
```

Config file: `config/cache.php`.

## Cache Drivers

### File Driver (default)

Stores cache as files in `storage/cache/`. No external service needed — ideal for development.

### Redis Driver

For production, Redis provides in-memory performance and supports cache invalidation:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Basic Usage

```php
use Siro\Core\Cache;

// Set a value (TTL in seconds)
Cache::set('user:1:profile', $userData, 3600);

// Get a value
$profile = Cache::get('user:1:profile');
// Returns null if key doesn't exist

// Check if key exists
if (Cache::has('user:1:profile')) {
    // Key exists
}

// Remove a key
Cache::forget('user:1:profile');

// Remove all cache
Cache::flush();
```

## Cache::remember()

The `remember()` method caches a value or computes and stores it if missing:

```php
$users = Cache::remember('active_users', 300, function () {
    return DB::table('users')->where('status', '=', 1)->get();
});
// Returns cached value if available, otherwise executes callback and caches result
```

This is the preferred pattern for read-heavy data.

## Query Caching

Cache expensive query results manually:

```php
$products = Cache::remember('products:active', 600, function () {
    return DB::table('products')
        ->where('status', '=', 'active')
        ->orderBy('created_at', 'DESC')
        ->limit(50)
        ->get();
});
```

Invalidate on data changes:

```php
// When a product is updated
Cache::forget('products:active');

// Or flush selectively with key patterns
```

## Config Cache

```bash
# Cache configuration files for faster boot
php siro config:cache

# Cache routes for faster routing
php siro route:cache

# Full optimization
php siro optimize
```

## Cache Status

```php
// Get cache request statistics
$status = Cache::requestStatus();
// Returns array with hits, misses, sets, etc.

// Reset in-memory request state
Cache::resetRequestState();
```

## Best Practices

- Use `Cache::remember()` as the primary caching pattern — it handles both get and set.
- Choose TTL based on data staleness tolerance: seconds for real-time, minutes for dashboards, hours for reference data.
- Use distinct, namespaced keys: `{context}:{id}:{purpose}` (e.g. `products:1:details`).
- Invalidate cache entries when underlying data changes, not on a timer.
- Use Redis in production for better performance and cross-process cache consistency.
- Never cache sensitive data (passwords, tokens, PII) without encryption.
- Monitor cache hit ratio to identify ineffective caching.
