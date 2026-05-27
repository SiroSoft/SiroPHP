---
title: Q ue ue
description: SiroPHP Q ue ue reference
sidebar_position: 21
sidebar_label: Q ue ue
---

# Queue API Reference

## Overview

Siro's queue system provides DB-based job processing with exponential backoff, timeouts, priority, and failed job retry.

```php
use Siro\Core\Queue;
```

---

## Configuration

```env
# .env
QUEUE_DRIVER=database
QUEUE_RETRY_AFTER=60      # seconds before retry
QUEUE_MAX_ATTEMPTS=3
```

Create the jobs table:

```bash
php siro make:queue-table
php siro migrate
```

---

## Defining Jobs

### Generate

```bash
php siro make:job SendWelcomeEmail
```

### Job Class

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use Siro\Core\Job;
use App\Models\User;

final class SendWelcomeEmail extends Job
{
    public function __construct(
        private readonly int $userId,
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if ($user === null) return;

        Mail::to($user->email)->send(new WelcomeMail($user));
    }

    public function failed(\Throwable $e): void
    {
        Logger::error('Welcome email failed', [
            'user_id' => $this->userId,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

## Dispatching Jobs

```php
// Simple dispatch
Queue::push(new SendWelcomeEmail($user->id));

// Delayed dispatch (seconds)
Queue::later(3600, new SendWelcomeEmail($user->id));

// With priority (higher = sooner)
Queue::push(new SendWelcomeEmail($user->id), priority: 10);
```

---

## Processing Jobs

```bash
# Process next available job
php siro queue:work

# Process continuously
php siro queue:work --daemon

# Process specific queue
php siro queue:work --queue=emails
```

---

## Job Properties

```php
class SendWelcomeEmail extends Job
{
    // Max retry attempts (default: 3)
    public int $maxAttempts = 5;

    // Timeout in seconds (default: 60)
    public int $timeout = 30;

    // Priority (higher = processed first)
    public int $priority = 0;

    // Queue name
    public string $queue = 'default';
}
```

---

## Failed Jobs

```bash
# List failed jobs
php siro queue:status

# Retry all failed jobs
php siro queue:retry --all

# Retry specific job
php siro queue:retry --id=42

# Clear failed jobs
php siro queue:flush
```

---

## Job Lifecycle

```
dispatch → push to DB → queue:work picks up → handle()
                                          ↓
                                     on success → delete from DB
                                          ↓
                                     on failure → retry? → yes → wait + retry
                                                          → no  → mark as failed
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `push(Job $job, int $delay, int $priority)` | Push job to queue |
| `later(int $delay, Job $job)` | Push job with delay |
| `work()` | Process next job |
| `workAll()` | Process all available jobs |
| `retry(int\|string $id)` | Retry failed job |
| `retryAll()` | Retry all failed jobs |
| `failed()` | Get all failed jobs |
| `flush()` | Clear all failed jobs |
| `status()` | Queue status and stats |
