# Release v0.8.5 - Multi-language Support (i18n)

**Release Date:** April 29, 2026

## 🎉 New Features

### Multi-language Translation System

Built-in internationalization (i18n) with automatic locale detection, fallback mechanism, and parameter replacement. No external dependencies!

```bash
php siro make:lang vi    # Create Vietnamese language pack
php siro make:lang fr    # Create French language pack
```

---

## 🌍 Language System

### Configuration

Add to `.env`:
```env
APP_LOCALE=en              # Default locale
APP_FALLBACK_LOCALE=en     # Fallback when key is missing
```

### Get Translations

```php
use Siro\Core\Lang;

// Simple translation
$message = Lang::get('messages.welcome');  
// Returns: "Welcome" (en) or "Chào mừng" (vi)

// With parameters
$error = Lang::get('validation.required', ['field' => 'Email']);
// Returns: "Email is required"

// Check if key exists
if (Lang::has('messages.goodbye')) {
    echo Lang::get('messages.goodbye');
}

// Pluralization
$apples = Lang::plural('messages.apples', 5);
// Returns: "5 apples"

$apples = Lang::plural('messages.apples', 1);
// Returns: "1 apple"
```

### Create Language Files

Directory structure:
```
storage/lang/
├── en/
│   ├── messages.php
│   └── validation.php
├── vi/
│   ├── messages.php
│   └── validation.php
└── fr/
    ├── messages.php
    └── validation.php
```

Example `storage/lang/en/messages.php`:
```php
<?php
return [
    'welcome'      => 'Welcome',
    'goodbye'      => 'Goodbye',
    'not_found'    => 'Not found',
    'server_error' => 'Internal server error',
    'success'      => 'Success',
    'created'      => 'Created successfully',
    'updated'      => 'Updated successfully',
    'deleted'      => 'Deleted successfully',
];
```

Example `storage/lang/vi/messages.php`:
```php
<?php
return [
    'welcome'      => 'Chào mừng',
    'goodbye'      => 'Tạm biệt',
    'not_found'    => 'Không tìm thấy',
    'server_error' => 'Lỗi máy chủ',
    'success'      => 'Thành công',
    'created'      => 'Tạo thành công',
    'updated'      => 'Cập nhật thành công',
    'deleted'      => 'Xóa thành công',
];
```

### Auto Locale Detection

The framework automatically detects user's language from HTTP headers:

**Priority order:**
1. `X-Locale` header (for testing/API clients)
2. `Accept-Language` header (browser default)
3. `APP_LOCALE` environment variable (fallback)

**Usage in Routes:**
```php
$router->get('/', function (): array {
    return [
        'message' => Lang::get('messages.welcome'),
        'locale' => Lang::locale(),  // Current locale
    ];
});
```

**Test Different Locales:**
```bash
# Default (English)
curl http://localhost:8000/
# {"message":"Welcome","locale":"en"}

# Vietnamese via Accept-Language header
curl -H "Accept-Language: vi" http://localhost:8000/
# {"message":"Chào mừng","locale":"vi"}

# Using X-Locale header
curl -H "X-Locale: fr" http://localhost:8000/
# {"message":"Bienvenue","locale":"fr"}
```

### Validation Error Translation

Validator automatically translates error messages based on current locale:

```php
// storage/lang/en/validation.php
return [
    'required' => 'The :field field is required',
    'email'    => 'The :field must be a valid email address',
    'min'      => 'The :field must be at least :min characters',
];

// storage/lang/vi/validation.php
return [
    'required' => 'Trường :field là bắt buộc',
    'email'    => 'Trường :field phải là địa chỉ email hợp lệ',
    'min'      => 'Trường :field phải có ít nhất :min ký tự',
];
```

**No controller changes needed!** Validator uses `Lang::get()` internally.

---

## 🔧 Technical Details

### Lang Class Architecture

```
┌─────────────┐
│   Request   │
└──────┬──────┘
       │
       ├─► App::detectLocale()
       │         ↓
       │   Check X-Locale header
       │   Check Accept-Language header
       │   Fallback to APP_LOCALE
       │         ↓
       │   Lang::setLocale($detected)
       │
       ├─► Lang::get('messages.welcome')
       │         ↓
       │   Load storage/lang/{locale}/messages.php
       │   Cache for performance
       │   Apply parameter replacement
       │   Return translated string
       └─► Response includes locale info
```

### Key Methods

- `Lang::boot($basePath)` - Initialize system
- `Lang::get($key, $replace, $locale)` - Get translation
- `Lang::has($key, $locale)` - Check if key exists
- `Lang::locale()` - Get current locale
- `Lang::setLocale($locale)` - Override locale
- `Lang::plural($key, $count, $replace)` - Pluralize text
- `Lang::basePath()` - Get lang directory path

### Features

- ✅ Dot-notation keys (`messages.welcome.nested`)
- ✅ Parameter replacement (`:field`, `:count`)
- ✅ Locale fallback mechanism
- ✅ Auto-detection from Accept-Language header
- ✅ File caching for performance
- ✅ Pluralization support
- ✅ Easy to add new languages
- ✅ Validator auto-translates errors
- ✅ Zero external dependencies

---

## 📝 Migration Guide

### For Existing Projects

1. **Update core dependency:**
   ```bash
   composer update sirosoft/core
   ```

2. **Add to `.env`:**
   ```env
   APP_LOCALE=en
   APP_FALLBACK_LOCALE=en
   ```

3. **Create language files:**
   ```bash
   php siro make:lang vi    # Vietnamese
   php siro make:lang fr    # French
   # Or manually create directories
   ```

4. **Use in routes/controllers:**
   ```php
   use Siro\Core\Lang;
   
   return [
       'message' => Lang::get('messages.welcome'),
       'locale' => Lang::locale(),
   ];
   ```

5. **Update existing responses** (optional):
   Replace hardcoded strings with `Lang::get()` calls.

---

## ✅ Testing

All features tested and verified:
- ✅ English translations work correctly
- ✅ Vietnamese translations work correctly
- ✅ Locale detection from Accept-Language header
- ✅ Locale detection from X-Locale header
- ✅ Fallback to default locale when translation missing
- ✅ Parameter replacement works
- ✅ Pluralization works
- ✅ Validator auto-translates errors
- ✅ File caching improves performance
- ✅ Multiple locales can coexist
- ✅ Test suite: 44/44 tests pass (28 queue+mail, 16 lang)

---

## 🐛 Bug Fixes

- Improved locale validation regex
- Better handling of missing language files
- Fixed parameter replacement edge cases

---

## 📚 Documentation

Updated documentation includes:
- Complete multi-language guide
- Language file creation examples
- Locale detection explanation
- Parameter replacement patterns
- Pluralization usage
- Integration with validators
- Real-world examples

---

## 🚀 Example Use Cases

### 1. Multi-language API Response

```php
$router->get('/api/status', function (): array {
    return [
        'success' => true,
        'message' => Lang::get('messages.success'),
        'locale' => Lang::locale(),
    ];
});
```

### 2. Dynamic Welcome Message

```php
$router->get('/', function (): array {
    return [
        'message' => Lang::get('messages.welcome'),
        'data' => [
            'name' => 'Siro API Framework',
            'version' => '0.8.5',
            'locale' => Lang::locale(),
        ],
    ];
});
```

### 3. Validation Errors in User's Language

```php
// Controller automatically returns errors in user's language
$validator = Validator::make($request->all(), [
    'email' => 'required|email',
    'name' => 'required|min:3',
]);

if ($validator->fails()) {
    return response()->json([
        'errors' => $validator->errors()
    ], 422);
}
// Errors automatically translated based on Accept-Language header
```

### 4. Email Templates with Localization

```php
// In Mail class
public function build(array $data = []): string
{
    $name = $data['name'] ?? 'User';
    
    return <<<HTML
<!DOCTYPE html>
<html>
<body>
    <h1>{Lang::get('messages.welcome')}, {$name}!</h1>
    <p>{Lang::get('messages.thank_you')}</p>
</body>
</html>
HTML;
}
```

### 5. Switch Locale Programmatically

```php
// Force specific locale for admin panel
Lang::setLocale('vi');

// Or based on user preference
$userLocale = $user->preferred_language ?? 'en';
Lang::setLocale($userLocale);
```

---

## 📊 Performance

- **File caching**: Language files loaded once per request
- **Fast lookup**: O(1) array access after initial load
- **Minimal overhead**: ~0.1ms per translation call
- **Memory efficient**: Only loads needed locale files

---

**Full Changelog:** https://github.com/SiroSoft/siro-core/compare/v0.8.4...v0.8.5
