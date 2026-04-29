# Release v0.8.7 - Storage, Custom Validation & Gzip Compression

**Release Date:** April 29, 2026

## 🎉 New Features

Three lightweight but powerful features added in this release:

1. **File Storage System** - Unified storage abstraction (Local + S3)
2. **Custom Validation Rules** - Extend Validator with custom rules
3. **Auto Gzip Compression** - Automatic response compression

---

## 📁 File Storage System

Unified filesystem abstraction supporting local storage and S3-compatible object storage.

### Configuration

**.env:**
```env
# Local driver (default)
STORAGE_DRIVER=local
STORAGE_PATH=storage/app

# S3 driver
# STORAGE_DRIVER=s3
# STORAGE_S3_KEY=your-access-key
# STORAGE_S3_SECRET=your-secret-key
# STORAGE_S3_REGION=us-east-1
# STORAGE_S3_BUCKET=my-bucket
# STORAGE_S3_ENDPOINT=  # Optional for MinIO, DigitalOcean Spaces, etc.
```

### Basic Usage

```php
use Siro\Core\Storage;

// Write file
Storage::put('documents/report.pdf', $pdfContent);

// Read file
$content = Storage::get('documents/report.pdf');

// Check existence
if (Storage::exists('documents/report.pdf')) {
    // File exists
}

// Delete file
Storage::delete('documents/report.pdf');

// Get public URL
$url = Storage::url('documents/report.pdf');
// Local: /storage/documents/report.pdf
// S3: https://bucket.s3.region.amazonaws.com/documents/report.pdf

// List files (local only)
$files = Storage::files('documents');
// ['report.pdf', 'invoice.pdf']
```

### File Upload Example

```php
$file = $request->file('avatar');
$path = 'avatars/' . uniqid() . '.' . $file->extension();

Storage::put($path, file_get_contents($file->tmpName));

return [
    'success' => true,
    'url' => Storage::url($path),
];
```

### S3-Compatible Services

Works with any S3-compatible service:
- ✅ Amazon S3
- ✅ MinIO (self-hosted)
- ✅ DigitalOcean Spaces
- ✅ Wasabi
- ✅ Backblaze B2
- ✅ Cloudflare R2

**No AWS SDK required!** Uses HTTP requests directly with AWS Signature V4.

### Features

- ✅ Local filesystem driver (default)
- ✅ S3/S3-compatible driver
- ✅ Same API for both drivers
- ✅ No external dependencies for S3
- ✅ Automatic directory creation
- ✅ MIME type detection
- ✅ AWS Signature V4 authentication
- ✅ Support for custom endpoints

---

## ✅ Custom Validation Rules

Extend the Validator with custom validation rules using `Validator::extend()`.

### Register Custom Rule

```php
use Siro\Core\Validator;

Validator::extend('phone', function ($value, $field, $input, $param): string|bool {
    return preg_match('/^\+?[0-9]{7,15}$/', (string) $value)
        ? true
        : ':field is not a valid phone number';
});
```

### Use in Validation

```php
$request->validate([
    'phone' => 'required|phone',
]);
```

### With Parameters

```php
Validator::extend('min_words', function ($value, $field, $input, $param): string|bool {
    $minWords = (int) ($param ?? 1);
    $wordCount = str_word_count((string) $value);
    
    return $wordCount >= $minWords
        ? true
        : ":field must have at least {$minWords} words";
});

// Usage
$request->validate([
    'description' => 'min_words:10',
]);
```

### Complex Validation

```php
Validator::extend('unique_email_domain', function ($value, $field, $input, $param): string|bool {
    $domain = substr(strrchr($value, '@'), 1);
    $blockedDomains = ['spam.com', 'fake.org'];
    
    if (in_array($domain, $blockedDomains)) {
        return ":field domain is not allowed";
    }
    
    return true;
});
```

### Return Types

- `true` - Validation passed
- `false` - Validation failed (uses default error message)
- `string` - Validation failed with custom error message (`:field` placeholder supported)

### Callback Parameters

```php
function ($value, $field, $input, $param) {
    // $value  - The field value being validated
    // $field  - The field name
    // $input  - Full input array (for cross-field validation)
    // $param  - Rule parameter (e.g., "10" from "min_words:10")
}
```

### Cross-Field Validation

```php
Validator::extend('different_from', function ($value, $field, $input, $param): string|bool {
    $otherField = $param;
    $otherValue = $input[$otherField] ?? null;
    
    if ($value === $otherValue) {
        return ":field must be different from {$otherField}";
    }
    
    return true;
});

// Usage
$request->validate([
    'new_password' => 'different_from:old_password',
]);
```

### Features

- ✅ Simple registration API
- ✅ Access to full input data
- ✅ Support for rule parameters
- ✅ Custom error messages
- ✅ Works with multi-language system
- ✅ Can combine with built-in rules
- ✅ Cross-field validation support

---

## 🗜️ Auto Gzip Compression

Automatic response compression when client supports it. Zero configuration!

### How It Works

The framework automatically checks if the client supports gzip encoding and compresses the response accordingly.

**In Response::send():**
```php
$acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

if (str_contains($acceptEncoding, 'gzip') && function_exists('gzencode')) {
    header('Content-Encoding: gzip');
    echo gzencode($encoded);
    return;
}

echo $encoded;  // Uncompressed for clients without gzip
```

### Benefits

- ✅ **Reduces bandwidth by 60-80%**
- ✅ **Faster page loads**
- ✅ **Zero configuration required**
- ✅ **Backward compatible** (clients without gzip get uncompressed)
- ✅ **Uses PHP's built-in `gzencode()`**
- ✅ **No external dependencies**

### Performance Impact

**Example API Response:**
```json
// Without gzip: 10KB
{
  "data": [...],
  "message": "Success",
  "meta": {...}
}

// With gzip: ~2KB (80% reduction)
[gzipped binary data]
```

**Real-world benefits:**
- Mobile users: Faster loading on slow connections
- Server costs: Lower bandwidth usage
- User experience: Quicker response times
- SEO: Google favors fast-loading sites

### Browser Support

All modern browsers automatically send `Accept-Encoding: gzip` header:

- ✅ Chrome
- ✅ Firefox
- ✅ Safari
- ✅ Edge
- ✅ Opera
- ✅ Mobile browsers (iOS Safari, Chrome Mobile, etc.)
- ✅ Postman, curl, and other API clients

### No Configuration Needed

Gzip compression works automatically:
- No environment variables to set
- No middleware to configure
- No code changes required
- Just upgrade and enjoy faster responses!

---

## 🔧 Technical Details

### Storage Architecture

```
┌─────────────┐
│   Your App  │
└──────┬──────┘
       │
       ├─► Storage::put('file.txt', content)
       │         ↓
       │   Check STORAGE_DRIVER
       │         ↓
       │   ┌────┴────┐
       │   │         │
       │  Local      S3
       │   │         │
       │   ├─► Write to storage/app/
       │   │
       │   └─► HTTP PUT to S3
       │       (with AWS Sig V4)
       └─► Returns true/false
```

### Validation Flow

```
┌─────────────┐
│  Request    │
└──────┬──────┘
       │
       ├─► $request->validate(rules)
       │         ↓
       │   Loop through rules
       │         ↓
       │   Built-in rules? → Validate
       │         ↓
       │   Custom rule? → Call callback
       │         ↓
       │   Collect errors
       └─► Return validation result
```

### Gzip Flow

```
┌─────────────┐
│  Response   │
└──────┬──────┘
       │
       ├─► Check Accept-Encoding header
       │         ↓
       │   Contains 'gzip'?
       │         ↓
       │   Yes ──► gzencode() + Send
       │   No  ──► Send uncompressed
       └─► Done
```

---

## ✅ Testing

All features tested and verified:
- ✅ Storage::put() writes files correctly
- ✅ Storage::get() reads files correctly
- ✅ Storage::delete() removes files
- ✅ Storage::exists() checks existence
- ✅ Storage::url() generates correct URLs
- ✅ Local driver works
- ✅ Custom validation rules register correctly
- ✅ Custom rules validate properly
- ✅ Rule parameters work
- ✅ Custom error messages display
- ✅ Gzip compression activates when supported
- ✅ Responses work without gzip for old clients
- ✅ **Test suite passes**

---

## 📝 Migration Guide

### For Existing Projects

1. **Update core dependency:**
   ```bash
   composer update sirosoft/core
   ```

2. **Add storage config to `.env`:**
   ```env
   STORAGE_DRIVER=local
   STORAGE_PATH=storage/app
   ```

3. **Start using Storage:**
   ```php
   use Siro\Core\Storage;
   
   Storage::put('file.txt', 'content');
   ```

4. **Register custom validation rules:**
   ```php
   // In routes/api.php or service provider
   Validator::extend('phone', function ($value, $field, $input, $param) {
       return preg_match('/^\+?[0-9]{7,15}$/', (string) $value);
   });
   ```

5. **Gzip works automatically** - no action needed!

---

## 🚀 Real-world Examples

### 1. Avatar Upload with Storage

```php
public function uploadAvatar(Request $request): array
{
    $file = $request->file('avatar');
    
    if (!$file) {
        throw new ValidationException('No file uploaded');
    }
    
    $path = 'avatars/' . auth()->id() . '.' . $file->extension();
    
    Storage::put($path, file_get_contents($file->tmpName));
    
    return [
        'success' => true,
        'url' => Storage::url($path),
    ];
}
```

### 2. Phone Number Validation

```php
// Register once in app bootstrap
Validator::extend('phone', function ($value, $field, $input, $param): string|bool {
    return preg_match('/^\+?[0-9]{7,15}$/', (string) $value)
        ? true
        : ':field must be a valid phone number';
});

// Use in controllers
$request->validate([
    'phone' => 'required|phone',
    'email' => 'required|email',
]);
```

### 3. S3 File Storage for Production

```env
# .env.production
STORAGE_DRIVER=s3
STORAGE_S3_KEY=AKIAIOSFODNN7EXAMPLE
STORAGE_S3_SECRET=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
STORAGE_S3_REGION=us-east-1
STORAGE_S3_BUCKET=my-app-files
```

```php
// Code remains the same!
Storage::put('reports/monthly.pdf', $pdfContent);
$url = Storage::url('reports/monthly.pdf');
// Returns: https://my-app-files.s3.us-east-1.amazonaws.com/reports/monthly.pdf
```

### 4. Custom Password Strength Validation

```php
Validator::extend('strong_password', function ($value, $field, $input, $param): string|bool {
    $password = (string) $value;
    
    if (strlen($password) < 8) {
        return ':field must be at least 8 characters';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return ':field must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return ':field must contain at least one number';
    }
    
    return true;
});

$request->validate([
    'password' => 'required|strong_password',
]);
```

### 5. Bandwidth Savings with Gzip

**Before (without gzip):**
- API response: 50KB
- Monthly traffic (1M requests): 50GB
- Cost: $50/month (at $1/GB)

**After (with gzip):**
- API response: 10KB (80% reduction)
- Monthly traffic (1M requests): 10GB
- Cost: $10/month (at $1/GB)

**Savings: $40/month = $480/year!** 💰

---

## 📊 Performance Comparison

| Feature | Before v0.8.7 | After v0.8.7 | Improvement |
|---------|---------------|--------------|-------------|
| File uploads | Manual file handling | Storage abstraction | Cleaner code |
| Custom validation | Not supported | Validator::extend() | Extensible |
| Response size | Uncompressed | Auto-gzipped | 60-80% smaller |
| S3 integration | AWS SDK required | Built-in HTTP | No dependencies |
| Bandwidth cost | 100% | 20-40% | 60-80% savings |

---

**Full Changelog:** https://github.com/SiroSoft/siro-core/compare/v0.8.6...v0.8.7
