# Release v0.8.3 - Storage Link & Cron Scheduling

**Release Date:** April 29, 2026

## 🎉 New Features

### 1. Storage Symbolic Link Command

Create symbolic links to serve uploaded files from the `storage/` directory via web server.

```bash
php siro storage:link
```

**Features:**
- Creates symlink: `public/storage` → `storage/public`
- Automatic fallback to Windows junction if symlink fails
- Enables serving uploaded files at `/storage/...` URLs
- Cross-platform support (Linux, macOS, Windows)

**Usage Example:**
```php
// Upload a file
$file = $request->file('avatar');
$path = $file->store('avatars'); // Saves to storage/public/avatars/xxx.jpg

// Access via URL
// http://yoursite.com/storage/avatars/xxx.jpg
```

---

### 2. Task Scheduler with Cron Support

Run scheduled tasks automatically using Laravel-like scheduling syntax.

```bash
php siro schedule:run
```

**Setup Crontab:**
```bash
# Run every minute
* * * * * cd /path/to/project && php siro schedule:run
```

**Define Scheduled Tasks** in `routes/schedule.php`:

```php
<?php

// Run CLI command daily at midnight
$schedule->command('db:seed UserSeeder')->daily();

// Run closure every hour
$schedule->call(function () {
    // Clean old logs
    foreach (glob('storage/logs/traces/*.json') ?: [] as $file) {
        if (filemtime($file) < time() - 86400 * 7) {
            @unlink($file);
        }
    }
})->hourly();

// Custom cron expression (Monday 6:00 AM)
$schedule->command('report:weekly')->cron('0 6 * * 1');

// Call class method
$schedule->call([\App\Crons\HealthCheck::class, 'run'])->hourly();
```

**Available Scheduling Methods:**
- `->everyMinute()` - Every minute
- `->hourly()` - Every hour (minute 0)
- `->daily()` - Midnight daily
- `->dailyAt('06:30')` - Specific time daily
- `->weekly()` - Sunday midnight
- `->monthly()` - First day of month
- `->cron('0 6 * * 1')` - Custom cron expression

**Example Cron Job Class:**

```php
<?php
// app/Crons/HealthCheck.php
namespace App\Crons;

final class HealthCheck
{
    public static function run(): void
    {
        $dbOk = true;
        try {
            \Siro\Core\Database::connection()->query('SELECT 1');
        } catch (\Throwable) {
            $dbOk = false;
        }

        \Siro\Core\Logger::request(
            'CRON',
            '/health-check',
            $dbOk ? 200 : 500,
            0,
            'system',
            'cron-health'
        );
    }
}
```

---

## 📦 Updated Dependencies

- **sirosoft/core**: `^0.8.2` → `^0.8.3`

---

## 🔧 Technical Details

### StorageLinkCommand
- Location: `siro-core/Commands/StorageLinkCommand.php`
- Creates symlinks with automatic Windows junction fallback
- Checks for existing links to prevent duplicates
- Provides clear error messages on failure

### Schedule System
- Location: `siro-core/Schedule.php`, `siro-core/Commands/ScheduleRunCommand.php`
- Event-based task execution
- Prevents duplicate runs within same minute
- Supports closures, CLI commands, and class methods
- Logs all executed tasks

---

## 📝 Migration Guide

### For Existing Projects

1. **Update core dependency:**
   ```bash
   composer update sirosoft/core
   ```

2. **Create storage link (optional):**
   ```bash
   php siro storage:link
   ```

3. **Add crontab entry (optional):**
   ```bash
   crontab -e
   # Add: * * * * * cd /path/to/project && php siro schedule:run
   ```

4. **Define scheduled tasks** in `routes/schedule.php` (create if not exists)

---

## ✅ Testing

All features tested and verified:
- ✅ Storage symlink creation on Linux/macOS/Windows
- ✅ Schedule runner executes tasks correctly
- ✅ Prevents duplicate executions within same minute
- ✅ Supports all scheduling methods
- ✅ Logs task execution properly

---

## 🐛 Bug Fixes

- Fixed MakeControllerCommand naming issue (minor warning)
- Improved error handling in symlink creation

---

## 📚 Documentation

Updated documentation includes:
- Storage link usage examples
- Complete scheduler API reference
- Cron job implementation patterns
- Best practices for scheduled tasks

---

**Full Changelog:** https://github.com/SiroSoft/siro-core/compare/v0.8.2...v0.8.3
