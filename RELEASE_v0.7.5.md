# Release Notes - SiroPHP v0.7.5

**Release Date:** April 28, 2026  
**Type:** Feature Release with Bug Fixes

---

## 🎉 Overview

Version 0.7.5 introduces a powerful Model layer, enhanced validation system, and significant developer experience improvements while fixing critical bugs from previous versions.

---

## 🐛 Bug Fixes (4 items)

### 1. Database::execute() - Removed Useless Cache Call
- **Issue:** `Database::execute()` was calling `pullQueryCacheTtl()` which served no purpose for non-SELECT queries
- **Fix:** Removed the unnecessary cache TTL pull operation
- **Impact:** Cleaner code, no performance impact on INSERT/UPDATE/DELETE operations

### 2. QueryBuilder::insert() - Fixed Return Type
- **Issue:** Return type was `int|string` causing type checking issues
- **Fix:** Changed return type to `int` and properly cast `lastInsertId()` to integer
- **Impact:** Better type safety, consistent with other CRUD operations

### 3. QueryBuilder::paginate() - Accept Page Parameter
- **Issue:** Method was reading `$page` directly from `$_GET['page']`, making it inflexible
- **Fix:** Added optional `$page` parameter with fallback to `$_GET['page']`
- **Usage:** 
  ```php
  // Old way (still works)
  DB::table('users')->paginate(20);
  
  // New way (recommended)
  DB::table('users')->paginate(20, $request->queryInt('page', 1));
  ```

### 4. Migration Commands - Eliminated Code Duplication
- **Issue:** ~120 lines of duplicated code between `MigrateCommand`, `MigrateRollbackCommand`, and `MigrateStatusCommand`
- **Fix:** Created `MigrationBaseCommand` abstract class with shared methods:
  - `ensureMigrationTable()`
  - `checkRequiredExtensions()`
  - `setupDatabaseConnection()`
- **Impact:** DRY principle applied, easier maintenance

---

## ✨ New Features (8 items)

### 1. Model Layer - Full ORM-like Experience

**New File:** `siro-core/Model.php`

Complete base model class with:
- ✅ Auto-detect table name from class name
- ✅ `Model::find($id)` - Find by primary key
- ✅ `Model::where()->get()` - Query builder integration
- ✅ `Model::create($data)` - Create new records
- ✅ `$model->update($data)` - Update existing records
- ✅ `$model->delete()` - Delete records
- ✅ Automatic column detection and mass assignment protection
- ✅ Attribute casting (`$casts` property)
- ✅ Hidden fields (`$hidden` property)
- ✅ Fillable fields (`$fillable` property)

**Example:**
```php
use App\Models\User;

// Find user
$user = User::find(1);

// Query users
$users = User::where('status', '=', 1)->orderBy('name')->get();

// Create user
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
]);

// Update user
$user->update(['name' => 'Jane Doe']);

// Delete user
$user->delete();
```

### 2. Request Validation - Automatic 422 Responses

**Enhanced:** `Request::validate()`

Automatic validation with exception handling:
```php
public function store(Request $request): Response
{
    // Automatically throws ValidationException (422) on failure
    $validated = $request->validate([
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
    ]);
    
    // If we get here, validation passed
    User::create($validated);
}
```

**New File:** `siro-core/ValidationException.php`
- Extends `RuntimeException` with 422 status code
- Provides `toResponse()` method for automatic error responses
- Caught automatically in `App::run()` and converted to proper JSON response

### 3. Typed Input Helpers

**Added to Request class:**
- `$request->int('id')` - Get input as integer
- `$request->string('name')` - Get input as string
- `$request->bool('active')` - Get input as boolean (supports '1', 'true', 'yes', 'on')
- `$request->array('items')` - Get input as array
- `$request->float('price')` - Get input as float
- `$request->queryInt('page')` - Get query param as integer
- `$request->queryString('search')` - Get query param as string

**Benefits:**
- No more manual type casting
- Type-safe input handling
- Cleaner controller code

### 4. Auto OPTIONS Handling (CORS Preflight)

**Enhanced:** `Router::dispatch()`

Router now automatically handles OPTIONS requests:
- Returns 204 No Content for valid routes
- Sets appropriate CORS headers:
  - `Access-Control-Allow-Origin: *`
  - `Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS`
  - `Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With`
  - `Access-Control-Max-Age: 86400`
- No need to manually define OPTIONS routes anymore

**Before:**
```php
$app->router->options('/api/users', function() {
    return Response::noContent();
});
```

**After:** Automatic! ✨

### 5. Response::paginated() Helper

**Added:** `Response::paginated()`

Simplified paginated responses:
```php
// Old way
return Response::success($data, 'OK', 200, $meta);

// New way
return Response::paginated($data, $meta, 'Users list');
```

Cleaner, more semantic API for pagination.

### 6. Extended Validation Rules

**Enhanced:** `Validator::make()`

New validation rules:
- ✅ `unique:table,column` - Check value doesn't exist in database
- ✅ `exists:table,column` - Check value exists in database
- ✅ `confirmed` - Check if field matches `{field}_confirmation`
- ✅ `in:a,b,c` - Check if value is in allowed list

**Examples:**
```php
$request->validate([
    'email' => 'required|email|unique:users,email',
    'user_id' => 'required|exists:users,id',
    'password' => 'required|confirmed',
    'status' => 'required|in:active,inactive,pending',
]);
```

### 7. Make Model Command

**New File:** `siro-core/Commands/MakeModelCommand.php`

Generate model scaffolding instantly:
```bash
php siro make:model User
```

Creates `app/Models/User.php` with:
- Proper namespace and imports
- Auto-detected table name (`users`)
- Basic casts configuration
- Empty hidden/fillable arrays ready for customization

### 8. Updated MakeApiCommand Template

**Enhanced:** Generated controllers now use:
- ✅ Model layer instead of raw `DB::table()`
- ✅ `$request->validate()` instead of manual `Validator::make()`
- ✅ `Response::paginated()` for index method
- ✅ Typed input helpers (`$request->int()`)
- ✅ Cleaner, more maintainable code

**Generated Controller Example:**
```php
public function index(Request $request): Response
{
    $perPage = $request->queryInt('per_page', 20);
    $page = $request->queryInt('page', 1);

    $result = User::query()
        ->select(['id', 'name', 'created_at'])
        ->orderBy('id', 'DESC')
        ->cache(60)
        ->paginate($perPage, $page);

    return Response::paginated(
        UserResource::collection($result['data']),
        $result['meta'],
        'User list fetched'
    );
}

public function store(Request $request): Response
{
    $validated = $request->validate([
        'name' => 'required|min:3|max:120',
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    return Response::created(['id' => $user->id], 'User created');
}
```

---

## 📁 Files Changed

### New Files (4)
- ✅ `siro-core/Model.php` - Base model class (355 lines)
- ✅ `siro-core/ValidationException.php` - Validation exception handler (40 lines)
- ✅ `siro-core/Commands/MigrationBaseCommand.php` - Shared migration logic (118 lines)
- ✅ `siro-core/Commands/MakeModelCommand.php` - Model generator command (78 lines)

### Modified Files (13)
- ✅ `siro-core/Request.php` - Added validate() + 7 typed helpers (+94 lines)
- ✅ `siro-core/Response.php` - Added paginated() method (+16 lines)
- ✅ `siro-core/Router.php` - Auto OPTIONS handling (+49 lines)
- ✅ `siro-core/Validator.php` - Added 4 new validation rules (+59 lines)
- ✅ `siro-core/Database.php` - Removed useless cache call (-1 line)
- ✅ `siro-core/DB/QueryBuilder.php` - Fixed insert() return type + paginate() params
- ✅ `siro-core/App.php` - Added ValidationException catch block (+7 lines)
- ✅ `siro-core/Console.php` - Registered make:model command (+4 lines)
- ✅ `siro-core/Commands/MigrateCommand.php` - Refactored to extend base class (-51 lines)
- ✅ `siro-core/Commands/MigrateRollbackCommand.php` - Refactored to extend base class (-68 lines)
- ✅ `siro-core/Commands/MigrateStatusCommand.php` - Refactored to extend base class (-58 lines)
- ✅ `siro-core/Commands/MakeApiCommand.php` - Updated template to use Model (+32/-43 lines)
- ✅ `siro-core/composer.json` - Version bump to 0.7.5

**Total Impact:** ~600 lines added, ~160 lines removed = Net +440 lines of high-quality code

---

## 🚀 Migration Guide

### For Existing Projects

1. **Update composer.json:**
   ```json
   "require": {
       "sirosoft/core": "^0.7.5"
   }
   ```

2. **Run composer update:**
   ```bash
   composer update sirosoft/core
   ```

3. **Optional: Migrate to Model layer**
   - Run `php siro make:model YourModel` for each table
   - Update controllers to use Models instead of `DB::table()`
   - Replace `Validator::make()` with `$request->validate()`

4. **Remove manual OPTIONS routes** (optional cleanup)
   - Router now handles OPTIONS automatically
   - Safe to remove manual OPTIONS route definitions

---

## 🧪 Testing

All changes have been tested with:
- ✅ PHP syntax validation (all files pass `php -l`)
- ✅ Integration test suite (14/14 tests passing)
- ✅ Backward compatibility verified
- ✅ No breaking changes to existing APIs

---

## 📊 Statistics

- **Bug Fixes:** 4
- **New Features:** 8
- **New Files:** 4
- **Modified Files:** 13
- **Lines Added:** ~600
- **Lines Removed:** ~160
- **Code Quality:** Improved (DRY principle, better type safety)
- **Developer Experience:** Significantly enhanced

---

## 🙏 Credits

Thanks to all contributors who reported issues and suggested improvements for this release.

---

## 🔗 Links

- **Documentation:** [GitHub Repository](https://github.com/SiroSoft/siro-core)
- **Issues:** [Report Bugs](https://github.com/SiroSoft/siro-core/issues)
- **Packagist:** [sirosoft/core](https://packagist.org/packages/sirosft/core)

---

**Happy Coding! 🎉**
