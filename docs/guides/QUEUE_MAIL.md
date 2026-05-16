# Queue & Mail Guide

## Queue System

The queue system processes background jobs asynchronously using a database-driven queue.

### Configuration

```env
QUEUE_DRIVER=database
QUEUE_DEFAULT_MAX_ATTEMPTS=3
```

Requires a `jobs` table (created by the default migration).

### Pushing Jobs

```php
use Siro\Core\Queue;

// Push a job to the default queue
Queue::push(SendWelcomeEmail::class, [
    'email' => 'user@example.com',
    'name' => 'John',
]);

// With delay (seconds)
Queue::push(SendWelcomeEmail::class, ['email' => 'user@example.com'], 3600);

// With priority (higher = processed first)
Queue::push(UrgentJob::class, $data, 0, 10);

// With custom max attempts
Queue::push(FailableJob::class, $data, 0, 0, 5);
```

### Writing Jobs

```php
namespace App\Jobs;

final class SendWelcomeEmail
{
    public function handle(array $data = []): void
    {
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? 'User';

        if ($email === '') {
            return;
        }

        Mail::to($email)
            ->subject('Welcome to our platform!')
            ->html('<h1>Welcome, ' . htmlspecialchars($name) . '!</h1>')
            ->send();
    }
}
```

### Processing Jobs

```bash
php siro queue:work              # Process one job
php siro queue:work --daemon     # Run continuously
php siro queue:status            # Show queue status
php siro queue:retry <id>        # Retry failed job
```

```php
// In code
Queue::work();              // Process next available job
$count = Queue::workAll();  // Process all available jobs (returns count)
```

### Queue Status

```php
$pending = Queue::pendingCount();   // Number of pending jobs
$failed = Queue::failedCount();     // Number of failed jobs
```

### Failed Jobs

When a job exceeds max attempts, it moves to the `failed_jobs` table:

```bash
php siro queue:retry <id>    # Retry specific failed job
```

## Mail System

### Configuration

```env
MAIL_DRIVER=sendmail                 # or: smtp
MAIL_FROM_ADDRESS=noreply@localhost
MAIL_FROM_NAME="Siro API"

# SMTP settings (when MAIL_DRIVER=smtp)
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user
MAIL_PASSWORD=pass
```

Config file: `config/mail.php` loads from `.env`.

### Sending Mail

```php
use Siro\Core\Mail;

// Basic email
Mail::to('user@example.com')
    ->subject('Welcome!')
    ->html('<h1>Hello</h1><p>Welcome to our platform.</p>')
    ->send();
```

### Mail Templates

Create reusable mail classes:

```php
namespace App\Mails;

final class WelcomeMail
{
    public function build(array $data = []): string
    {
        $name = $data['name'] ?? 'User';
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        return '<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif;">
    <h1>Welcome, ' . $safeName . '!</h1>
    <p>Thank you for joining us.</p>
</body>
</html>';
    }
}

// Usage
Mail::to('user@example.com')
    ->subject('Welcome!')
    ->html((new WelcomeMail())->build(['name' => 'John']))
    ->send();
```

### Queueing Mail

Send emails asynchronously via the queue:

```php
// Queue the email (processed by queue worker)
Mail::to('queued@test.com')
    ->subject('Queued Email')
    ->html('<p>This email was queued.</p>')
    ->queue();  // Pushes SendMailJob to the queue

// Process queued mails
php siro queue:work
```

The `SendMailJob` reconstructs the mail from serialized data including `to`, `subject`, `body`, `content_type`, `cc`, `bcc`, `reply_to`, and attachments.

### SMTP Configuration

For production, use SMTP with TLS:

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.xxxxx
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Your App"
```

## Best Practices

- Always queue emails in production to avoid blocking HTTP responses.
- Run `php siro queue:work --daemon` as a supervised background process.
- Monitor failed jobs and set up alerts for repeated failures.
- Use descriptive job class names that reflect the action (e.g. `SendWelcomeEmail`).
- Keep job handlers idempotent — they may be retried.
- Set realistic timeouts for jobs that make external HTTP calls.
