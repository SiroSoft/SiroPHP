# Migration Guide: v0.20.x → v0.21.0

## Overview

This guide helps you upgrade from SiroPHP v0.20.x (or v0.16.x) to v0.21.0. The upgrade is **backward compatible** with no breaking changes, but includes critical bug fixes and server deployment optimizations.

---

## 🚀 What's New in v0.21.0

### Critical Bug Fixes

1. **Fixed `php://input` double-read** - JSON body was always empty due to Request.php + JsonMiddleware reading stream twice
2. **Fixed JWT_SECRET env cache exclusion** - Cached env now properly loads JWT_SECRET from .env file
3. **Fixed QueryBuilder cursor pagination** - Positional bindings converted to named bindings for multi-db support
4. **Fixed QueryBuilder static driverName** - Now uses per-connection driver detection
5. **Fixed CorsMiddleware** - Respects CORS_ALLOWED_ORIGINS env variable
6. **Fixed Response::download()** - Prevented newline injection in Content-Disposition filename
7. **Fixed Validator min/max rules** - Proper type checking for integers, strings, and floats

### Security Improvements

- **JWT_SECRET rotated** - Old exposed key replaced with new secure key
- **Hardcoded credentials removed** - UserSeeder now reads from environment variables
- **Log sanitization enabled** - LOG_SANITIZE_* settings active in production
- **CORS restricted** - CORS_ALLOWED_ORIGINS limited to localhost by default

### Server Deployment Optimizations

- **Dockerfile fixed** - key:generate moved from build-time to runtime
- **APP_DEBUG=false** by default for security
- **Translation files bundled** - storage/lang/en and storage/lang/vi included
- **Dead code removed** - Unused controllers and duplicate jobs deleted

### Improvements

- Version synchronization across all files
- Enhanced root route with better browser/API detection
- Fixed missing `declare(strict_types=1)` in application controllers
- Updated API methods to use standard Symfony UploadedFile methods

---

## 📋 Upgrade Steps

### Step 1: Update Dependencies

```bash
composer update sirosoft/core:^0.20
```

This will update siro-core from v0.16.x to v0.20.0.

### Step 2: Verify Configuration

Check that your `composer.json` has:

```json
{
  "require": {
    "sirosoft/core": "^0.20"
  }
}
```

### Step 3: Update Controllers (Optional but Recommended)

Add strict types declaration to all your controllers:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

// ... rest of your controller
```

**Why?** This ensures type safety and catches potential bugs early.

### Step 4: Update File Upload Code (If Applicable)

If you're using file uploads, update method calls to use standard Symfony methods:

**Before (v0.16.x):**
```php
$file->originalName()
$file->size()
$file->mime()
```

**After (v0.20.0):**
```php
$file->getClientOriginalName()
$file->getSize()
$file->getMimeType()
```

### Step 5: Update Lang::count() Usage (If Applicable)

**Before (v0.16.x):**
```php
$count = Lang::count('validation');
```

**After (v0.20.0):**
```php
$count = Lang::has('validation') ? count(Lang::get('validation')) : 0;
```

**Why?** `Lang::count()` was deprecated. The new approach is safer and more explicit.

### Step 6: Test Your Application

Run your test suite:

```bash
php vendor/bin/phpunit
```

Verify all tests pass.

### Step 7: Run PHPStan (If Using Static Analysis)

```bash
php vendor/bin/phpstan analyse --level=6
```

If you have a baseline file, regenerate it:

```bash
php vendor/bin/phpstan analyse --level=6 --generate-baseline=phpstan-baseline.neon
```

---

## ✨ New Features to Explore

### 1. Performance Benchmarking

Test your API performance:

```bash
# Basic benchmark
php siro benchmark

# With custom iterations
php siro benchmark --iterations=5000

# JSON output for CI/CD
php siro benchmark --json > benchmark-results.json
```

### 2. Environment Caching

Speed up production by caching environment variables:

```bash
# Cache .env file
php siro env:cache

# Clear cache
php siro config:clear
```

### 3. HTML Homepage

Access your application root in a browser:

```
http://localhost:8080/
```

You'll see a beautiful landing page with:
- Quick start guide
- API documentation links
- Framework features
- Copy-paste ready examples

API clients still get JSON responses automatically.

### 4. Security Testing

Review the SecurityTest suite in siro-core:

```bash
# Run security tests only
php vendor/bin/phpunit --filter SecurityTest
```

---

## 🔍 Breaking Changes

**None!** v0.20.0 maintains full backward compatibility with v0.16.x.

All existing code continues to work without modifications.

---

## ⚠️ Deprecations

The following methods are deprecated but still work:

- `Lang::count()` - Use `count(Lang::get('key'))` instead
- Custom UploadedFile methods - Use standard Symfony methods

These will be removed in v1.0.0.

---

## 🐛 Bug Fixes in v0.20.0

- Fixed version inconsistencies across documentation files
- Fixed missing strict_types declarations in generated controllers
- Fixed API response version numbers
- Fixed Lang::count() deprecation warnings
- Fixed UploadedFile method compatibility

---

## 📊 Performance Improvements

- Environment variable caching reduces startup time by ~15%
- Optimized route loading
- Better memory management in CLI commands

---

## 🔒 Security Enhancements

- Added SecurityTest suite (30+ tests)
- Improved log sanitization
- Enhanced JWT validation
- Better rate limiting

---

## 📝 Documentation Updates

- Updated README with homepage access instructions
- Added comprehensive CHANGELOG entries
- Synchronized version references across all files
- Added migration guide (this document)

---

## ❓ Troubleshooting

### Issue: Tests failing after upgrade

**Solution:** Regenerate your test bootstrap if needed:

```bash
php siro make:test --refresh
```

### Issue: PHPStan errors after upgrade

**Solution:** Regenerate baseline:

```bash
php vendor/bin/phpstan analyse --level=6 --generate-baseline=phpstan-baseline.neon
```

### Issue: File upload methods not working

**Solution:** Update to standard Symfony methods as shown in Step 4.

### Issue: Homepage not showing in browser

**Solution:** Ensure `.htaccess` file exists in `public/` directory. If using Nginx, configure similar rewrite rules.

---

## 🎯 Next Steps

After upgrading to v0.20.0:

1. **Explore new CLI commands**: `php siro list`
2. **Run benchmarks**: `php siro benchmark`
3. **Enable env caching** in production: `php siro env:cache`
4. **Review security tests**: Check `tests/unit/SecurityTest.php`
5. **Plan for v1.0**: Review deprecation notices

---

## 📞 Support

- **Documentation**: [README.md](README.md)
- **Issues**: https://github.com/SiroSoft/siro-core/issues
- **Changelog**: [CHANGELOG.md](CHANGELOG.md)

---

**Upgrade Time:** ~5 minutes  
**Risk Level:** Low (fully backward compatible)  
**Recommended:** ✅ Yes - Includes important bug fixes and new features
