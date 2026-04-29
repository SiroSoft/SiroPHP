# Release v0.8.4 - Queue System & Email Support

**Release Date:** April 29, 2026

## 🎉 New Features

### 1. Job Queue System

DB-based job queue with automatic retry, priority support, and failed job tracking. No external dependencies required!

```bash
php siro queue:work              # Process jobs
php siro queue:work --daemon     # Run continuously
php siro queue:status            # Check status
php siro queue:retry <id>        # Retry failed job
php siro queue:flush             # Clear failed jobs
```

**Push Jobs:**
```php
use Siro\Core\Queue;

// Simple job
Queue::push(SendEmailJob::class, ['to' => 'user@example.com']);

// With delay (1 hour)
Queue::push(ProcessReportJob::class, $data, delay: 3600);

// With priority
Queue::push(UrgentJob::class, $data, priority: 10);

// Custom retry and timeout
Queue::push(HeavyJob::class, $data, maxAttempts: 5, timeout: 300);
```

**Create Job Class:**
```php
<?php
namespace App\Jobs;

final class SendEmailJob
{
    public function handle(array $data = []): void
    {
        // Your logic here
        mail($data['to'], 'Subject', 'Body');
    }
}
```

**Features:**
- ✅ Automatic retry with exponential backoff (5s, 10s, 20s... max 300s)
- ✅ Priority support (higher priority runs first)
- ✅ Job timeout protection (default 120s)
- ✅ Failed jobs tracking in `failed_jobs` table
- ✅ Lock mechanism prevents duplicate processing
- ✅ Works with SQLite, MySQL, PostgreSQL
- ✅ Daemon mode for production (`--daemon` flag)
- ✅ Configurable sleep between polls

---

### 2. Email System

Send emails via sendmail or SMTP with no external dependencies. Full integration with queue system.

```php
use Siro\Core\Mail;

// Send immediately
Mail::to('user@example.com')
    ->subject('Welcome!')
    ->html('<h1>Hello!</h1>')
    ->send();

// Queue for async delivery
Mail::to('user@example.com')
    ->subject('Welcome!')
    ->html('<h1>Hello!</h1>')
    ->queue();

// Delayed delivery (1 hour)
Mail::to('user@example.com')
    ->subject('Welcome!')
    ->html('<h1>Hello!</h1>')
    ->sendLater(3600);
```

**Advanced Options:**
```php
Mail::to('user@example.com')
    ->subject('Report')
    ->html('<h1>Monthly Report</h1>')
    ->cc('manager@example.com')
    ->bcc('archive@example.com')
    ->replyTo('support@example.com')
    ->attach('/path/to/report.pdf')
    ->send();
```

**Configuration (.env):**
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Siro API"
```

**Create Email Templates:**
```php
<?php
namespace App\Mails;

final class WelcomeMail
{
    public function build(array $data = []): string
    {
        $name = $data['name'] ?? 'User';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<body>
    <h1>Welcome, {$name}!</h1>
    <p>Thank you for joining us.</p>
</body>
</html>
HTML;
    }
}
```

**Usage:**
```php
Mail::to('user@example.com')
    ->subject('Welcome!')
    ->html((new WelcomeMail())->build(['name' => 'John']))
    ->send();
```

**Features:**
- ✅ Sendmail driver (PHP mail())
- ✅ SMTP driver with STARTTLS and AUTH LOGIN
- ✅ No external dependencies (uses fsockopen)
- ✅ HTML and plain text support
- ✅ CC, BCC, Reply-To
- ✅ File attachments with MIME encoding
- ✅ Queue integration for async delivery
- ✅ Delayed delivery support
- ✅ Error logging and tracking

---

## 📦 Database Migration

New migration added: `create_jobs_table.php`

Creates two tables:
- **jobs** - Stores pending/processing jobs
- **failed_jobs** - Tracks failed jobs for retry

Run migration:
```bash
php siro migrate
```

---

## 🔧 Technical Details

### Queue Architecture

```
┌─────────────┐
│   Your App  │
└──────┬──────┘
       │
       ├─► Queue::push(JobClass, data)
       │         ↓
       │   ┌──────────┐
       │   │  jobs    │
       │   │  table   │
       │   └────┬─────┘
       │        │
       │  php siro queue:work
       │        ↓
       │   JobClass::handle(data)
       └─► Success → Delete from jobs
           Failure → Move to failed_jobs
```

### Mail Architecture

```
Mail::to()->send()
    ↓
┌──────────────┐
│ MAIL_DRIVER  │
└──┬───────┬───┘
   │       │
sendmail  smtp
   │       │
   │   fsockopen
   │   STARTTLS
   │   AUTH LOGIN
   │       │
   └───┬───┘
       ↓
  Email Sent
```

---

## 📝 Migration Guide

### For Existing Projects

1. **Update core dependency:**
   ```bash
   composer update sirosoft/core
   ```

2. **Run migrations:**
   ```bash
   php siro migrate
   ```

3. **Configure mail settings** in `.env`:
   ```env
   MAIL_DRIVER=smtp
   MAIL_HOST=smtp.example.com
   MAIL_PORT=587
   MAIL_USERNAME=user
   MAIL_PASSWORD=pass
   ```

4. **Start queue worker:**
   ```bash
   php siro queue:work
   
   # Or daemon mode for production
   php siro queue:work --daemon
   ```

5. **Setup crontab** (optional):
   ```bash
   * * * * * cd /path/to/project && php siro queue:work
   ```

---

## ✅ Testing

All features tested and verified:
- ✅ Queue push and process works correctly
- ✅ Exponential backoff retry logic
- ✅ Failed jobs tracking and retry
- ✅ Queue status command
- ✅ Mail send via sendmail
- ✅ Mail send via SMTP with TLS
- ✅ Mail queue integration
- ✅ File attachments work
- ✅ CC, BCC, Reply-To support
- ✅ Cross-database compatibility (SQLite, MySQL, PostgreSQL)

---

## 🐛 Bug Fixes

- Improved error handling in queue worker
- Better transaction management for job locking
- Fixed SMTP response parsing

---

## 📚 Documentation

Updated documentation includes:
- Complete queue system guide
- Email sending examples
- Job class creation patterns
- SMTP configuration guide
- Production deployment recommendations
- Crontab setup instructions

---

## 🚀 Example Use Cases

### 1. Welcome Email on User Registration

```php
// In UserController
public function register(Request $request): array
{
    $user = User::create($request->all());
    
    // Queue welcome email
    Queue::push(SendWelcomeEmailJob::class, [
        'email' => $user->email,
        'name' => $user->name
    ]);
    
    return ['success' => true, 'user' => $user];
}
```

### 2. Background Report Generation

```php
// Push heavy report job
Queue::push(GenerateReportJob::class, [
    'report_type' => 'monthly',
    'user_id' => $userId
], delay: 0, priority: 5);

// User gets immediate response
return ['message' => 'Report generation started'];
```

### 3. Scheduled Email Notifications

```php
// Send daily digest at 8 AM
$schedule->call(function () {
    $users = User::where('notifications', true)->get();
    
    foreach ($users as $user) {
        Mail::to($user->email)
            ->subject('Daily Digest')
            ->html($digestHtml)
            ->queue();
    }
})->dailyAt('08:00');
```

---

**Full Changelog:** https://github.com/SiroSoft/siro-core/compare/v0.8.3...v0.8.4
