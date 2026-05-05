# QA Round 2 Fixes - CLI Preflight Checks

## Overview

After QA Round 2 testing, identified that CLI database commands were failing with confusing PDO errors instead of clear extension validation messages.

---

## Issue Identified

### Problem: CLI Commands Don't Validate Extensions

**Before Fix:**
```bash
php siro migrate
# Error: PDOException: could not find driver
# (confusing - doesn't tell user WHAT to install)
```

**Expected Behavior:**
```bash
php siro migrate
# Error: Missing required PHP extensions: pdo_mysql (for mysql)
# Please install them or update your php.ini configuration.
```

---

## Root Cause

Extension preflight checks were only implemented in `App::boot()` (web requests), but NOT in CLI commands that directly access the database.

**Affected Commands:**
- `php siro migrate`
- `php siro migrate:status`
- `php siro migrate:rollback`

---

## Solution Applied

Added `checkRequiredExtensions()` method to all three migration commands with early validation before attempting PDO connection.

### Files Modified

1. ✅ `core/Commands/MigrateCommand.php`
2. ✅ `core/Commands/MigrateStatusCommand.php`
3. ✅ `core/Commands/MigrateRollbackCommand.php`

---

## Implementation Details

### Method Added to Each Command

```php
private function checkRequiredExtensions(): void
{
    $required = ['pdo', 'json'];
    $missing = [];

    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }

    // Check PDO drivers based on DB_CONNECTION
    $dbConnection = strtolower((string) Env::get('DB_CONNECTION', 'mysql'));
    $pdoDriver = match ($dbConnection) {
        'pgsql' => 'pdo_pgsql',
        'sqlite' => 'pdo_sqlite',
        default => 'pdo_mysql',
    };

    if (!extension_loaded($pdoDriver)) {
        $missing[] = $pdoDriver . " (for {$dbConnection})";
    }

    if ($missing !== []) {
        fwrite(STDERR, "Error: Missing required PHP extensions: " . implode(', ', $missing) . PHP_EOL);
        fwrite(STDERR, "Please install them or update your php.ini configuration." . PHP_EOL);
        exit(1);
    }
}
```

### Called Early in run() Method

```php
public function run(array $args): int
{
    Env::load($this->basePath . DIRECTORY_SEPARATOR . '.env');
    
    // Preflight check BEFORE Database::configure()
    $this->checkRequiredExtensions();
    
    // Now safe to configure and connect
    $config = require ...;
    Database::configure($config);
    $pdo = Database::connection();
    // ... rest of command
}
```

---

## Benefits

### 1. Clear Error Messages

**Before:**
```
PDOException: could not find driver
```

**After:**
```
Error: Missing required PHP extensions: pdo_mysql (for mysql)
Please install them or update your php.ini configuration.
```

### 2. Fail Fast

- Validation happens BEFORE attempting database connection
- No wasted time trying to connect with missing drivers
- Immediate actionable feedback

### 3. Consistent Behavior

- Web requests (`App::boot()`) → validates extensions
- CLI commands → now also validate extensions
- Same error messages across both contexts

### 4. Driver-Specific Messages

Automatically detects which PDO driver is needed based on `DB_CONNECTION`:

| DB_CONNECTION | Required Extension | Error Message |
|---------------|-------------------|---------------|
| mysql | pdo_mysql | "pdo_mysql (for mysql)" |
| pgsql | pdo_pgsql | "pdo_pgsql (for pgsql)" |
| sqlite | pdo_sqlite | "pdo_sqlite (for sqlite)" |

---

## Testing Scenarios

### Scenario 1: Missing pdo_mysql

```bash
# Environment without pdo_mysql
php siro migrate

Output:
Error: Missing required PHP extensions: pdo_mysql (for mysql)
Please install them or update your php.ini configuration.

Exit code: 1
```

✅ **Result:** Clear, actionable error message

---

### Scenario 2: All Extensions Present

```bash
# Environment with all required extensions
php siro migrate

Output:
Pending migrations: 2
Running batch: 1
Migrated: 20260427110100_create_users_table.php
Migrated: 20260427143000_add_token_version_to_users_table.php
Migration completed. Ran 2 migration(s).

Exit code: 0
```

✅ **Result:** Normal operation proceeds

---

### Scenario 3: Wrong PDO Driver

```bash
# .env has DB_CONNECTION=pgsql but pdo_pgsql not installed
php siro migrate

Output:
Error: Missing required PHP extensions: pdo_pgsql (for pgsql)
Please install them or update your php.ini configuration.

Exit code: 1
```

✅ **Result:** Correctly identifies needed driver for configured database

---

## Comparison: Web vs CLI Validation

### Web Request (App::boot)

```php
// In core/App.php
public function boot(): void
{
    Env::load(...);
    Logger::boot(...);
    $this->validateSecurityConfig();
    $this->checkRequiredExtensions();  // ← Validates here
    
    // Throws RuntimeException if missing
}
```

**Behavior:** Throws exception → caught by error handler → returns JSON error

---

### CLI Command (MigrateCommand, etc.)

```php
// In core/Commands/MigrateCommand.php
public function run(array $args): int
{
    Env::load(...);
    $this->checkRequiredExtensions();  // ← Validates here
    
    // Writes to STDERR and exits with code 1
}
```

**Behavior:** Writes to STDERR → exits with code 1

---

## Why Different Approaches?

### Web Context
- Can use exceptions (caught by framework error handler)
- Returns structured JSON response
- HTTP status codes available

### CLI Context
- Exceptions would show stack trace (ugly)
- Better to write clean error to STDERR
- Exit codes are standard for CLI tools
- Easier to parse in scripts/CI

---

## Remaining Issues from QA Round 2

### ❌ Not Fixed Yet (Requires Different Approach)

1. **JWT_SECRET Placeholder Issue**
   - When user copies `.env.example`, JWT_SECRET is placeholder
   - App boot fails with security validation error
   - **Solution needed:** Auto-generate key in post-create-project-cmd OR skip validation during initial setup

2. **Package Publishing**
   - `composer create-project siro/api` fails (package not on Packagist yet)
   - **Solution needed:** Publish to Packagist or provide alternative installation method

3. **Malformed JSON Handling in Bootstrap Failure**
   - When app fails to boot, returns HTML instead of JSON
   - **Solution needed:** Ensure error handler always returns JSON for API routes

---

## Files Changed Summary

| File | Lines Added | Purpose |
|------|-------------|---------|
| `core/Commands/MigrateCommand.php` | +34 | Preflight check method |
| `core/Commands/MigrateStatusCommand.php` | +33 | Preflight check method |
| `core/Commands/MigrateRollbackCommand.php` | +33 | Preflight check method |
| **Total** | **+100** | **3 commands updated** |

---

## Impact Assessment

### Before This Fix
- ❌ Confusing PDO errors
- ❌ No guidance on what to install
- ❌ Inconsistent with web request validation
- ❌ Poor developer experience

### After This Fix
- ✅ Clear, actionable error messages
- ✅ Specific extension names listed
- ✅ Consistent validation approach
- ✅ Professional CLI behavior

---

## Next Steps for Full QA Pass

To achieve full production readiness, still need to address:

### Priority 1: JWT Secret Handling
**Option A:** Auto-generate in `post-create-project-cmd`
```json
"scripts": {
  "post-create-project-cmd": [
    "@php siro key:generate"
  ]
}
```

**Option B:** Skip validation if `.env` just created
```php
if (file_exists('.env') && !Env::hasBeenModified()) {
    // Skip strict validation on first run
}
```

### Priority 2: Package Distribution
- Publish to Packagist.org
- Or provide Docker-based quick start
- Or provide download archive with setup script

### Priority 3: Error Handler Improvements
- Ensure all errors return JSON for API routes
- Even bootstrap failures should return proper format

---

## Commit Information

**Commit:** 26ffdfd  
**Message:** fix: add CLI preflight checks for DB commands  
**Date:** April 27, 2026  
**Files Changed:** 3 commands + documentation  

**Pushed to:** https://github.com/SiroSoft/SiroPHP

---

## Conclusion

CLI preflight checks now match web request validation behavior. Users get clear, actionable error messages when required PHP extensions are missing, preventing confusing PDO errors.

**Status:** ✅ CLI extension validation complete  
**Remaining:** JWT secret handling & package distribution
