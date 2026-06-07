---
title: Queue Guide
description: Background job processing with DB and Redis drivers
sidebar_position: 12
sidebar_label: Queue Guide
---

# Queue Guide

**Process heavy operations in the background** using SiroPHP's queue system. Supports the DB driver (default) and Redis driver for high-throughput scenarios.

---

## 📦 Configuration

The queue driver is set via the `QUEUE_DRIVER` environment variable:

```env
# .env
QUEUE_DRIVER=db       # Default — uses the database jobs table
# or
QUEUE_DRIVER=redis    # Redis-backed for high-throughput production
```

### DB Driver (Default)

Stores jobs in a database `jobs` table. Run the migration to create it:

```bash
php siro migrate
```

The migration is included in the skeleton — a `jobs` table is created with columns for payload, attempts, reserved_at, available_at, and created_at.

### Redis Driver

Uses Redis lists for fast push/pop operations. Requires the `QUEUE_DRIVER=redis` env var and a running Redis instance:

```env
QUEUE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
```

---

## 🧩 Creating Jobs

Generate a job class with the CLI:

```bash
php siro make:job SendWelcomeEmail
```

This creates `app/Jobs/SendWelcomeEmail.php`:

```php
<?php

namespace App\Jobs;

use Siro\Core\Queue\Job;

class SendWelcomeEmail extends Job
{
    public function __construct(
        protected array $payload,
    ) {
        parent::__construct($payload);
    }

    public function handle(): void
    {
        $user = $this->payload['user'];
        // Send email logic...
    }
}
```

### Dispatching Jobs

```php
use App\Jobs\SendWelcomeEmail;

// Dispatch with payload
SendWelcomeEmail::dispatch(['user_id' => 1, 'email' => 'user@example.com']);

// Or via the Queue facade
Queue::push(new SendWelcomeEmail(['user_id' => 1]));
```

### Delayed Jobs

```php
// Run 60 seconds from now
SendWelcomeEmail::dispatch(['user_id' => 1])->delay(60);
```

---

## ⚙️ Running the Worker

### Process Jobs Once

```bash
php siro queue:work
```

Processes a single job from the queue and exits.

### Daemon Mode (Continuous)

```bash
php siro queue:work --daemon
```

Runs continuously, processing jobs as they arrive. For production use with supervisor/systemd.

### Specify Queue

```bash
php siro queue:work --queue=high,default
```

Process jobs from the `high` queue first, then `default`.

---

## 🛠️ CLI Commands

### queue:work

```bash
php siro queue:work                    # Process one job
php siro queue:work --daemon           # Run continuously
php siro queue:work --queue=emails     # Specific queue name
php siro queue:work --tries=3          # Max retry attempts (default: 1)
php siro queue:work --delay=5          # Delay between retries in seconds
```

### queue:status

```bash
php siro queue:status
```

Shows queue statistics:
- Total jobs in queue
- Jobs currently being processed
- Failed jobs count
- Per-queue breakdown

### queue:retry

```bash
php siro queue:retry <id>      # Retry a specific failed job by ID
php siro queue:retry --all     # Retry all failed jobs
```

### queue:flush

```bash
php siro queue:flush            # Clear all pending jobs
php siro queue:flush --failed   # Clear only failed jobs
php siro queue:flush --queue=emails  # Clear a specific queue
```

---

## 📊 Queue Best Practices

1. **Use specific queue names** for different workloads: `emails`, `exports`, `images`
2. **Set appropriate retry limits** — use `--tries=3` for transient failures
3. **Monitor failed jobs** regularly with `php siro queue:status`
4. **Use the Redis driver** for high-throughput or distributed systems
5. **Supervise the daemon** with systemd/supervisor in production
6. **Keep jobs small** — dispatch many small jobs rather than one large one

---

## 🔍 Troubleshooting

### "No jobs table found"
Run `php siro migrate` to create the jobs table.

### "Redis connection refused"
Verify Redis is running and `REDIS_HOST`/`REDIS_PORT` are correct in `.env`.

### "Job failed after X attempts"
Check the failed jobs with `php siro queue:status`, inspect the payload, fix the issue, and run `php siro queue:retry --all`.

---

## 📖 Related Guides

- **[Mercure/WebSocket Guide](MERCURE.md)** — Real-time events
- **[Event System](EVENTS.md)** — Pub/sub event system
- **[Quick Start](QUICKSTART.md)** — Build your first API
