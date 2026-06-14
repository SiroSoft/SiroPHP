---
title: S ch ed ul e
description: SiroPHP S ch ed ul e reference
sidebar_position: 26
sidebar_label: S ch ed ul e
---

# Schedule API Reference

## Overview

Schedule tasks to run at defined intervals, like cron jobs inside PHP.

```php
use Siro\Core\Schedule;
```

---

## Define Tasks

```php
// In routes/schedule.php

Schedule::command('log:cleanup')
    ->daily();

Schedule::command('queue:work --queue=emails')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(function () {
    DB::table('sessions')->where('expires_at', '<', date('Y-m-d H:i:s'))->delete();
})->hourly();
```

---

## Frequency Methods

```php
// Most common
Schedule::command(...)->everyMinute();
Schedule::command(...)->everyFiveMinutes();
Schedule::command(...)->everyFifteenMinutes();
Schedule::command(...)->everyThirtyMinutes();
Schedule::command(...)->hourly();
Schedule::command(...)->daily();
Schedule::command(...)->dailyAt('03:00');
Schedule::command(...)->weekly();
Schedule::command(...)->monthly();

// Custom cron
Schedule::command(...)->cron('*/15 * * * *');
```

---

## Constraints

```php
Schedule::command('db:backup')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()    // Don't run if previous still running
    ->environments('production');  // Only in production
```

---

## Run Scheduler

```bash
# Add this to your server's crontab (runs every minute)
* * * * * cd /path/to/app && php siro schedule:run
```

---

## Available Methods

| Method | Description |
|--------|-------------|
| `command(string $command)` | Schedule a CLI command |
| `call(callable $callback)` | Schedule a PHP callback |
| `cron(string $expression)` | Set custom cron expression |
| `everyMinute()` | Every minute |
| `everyFiveMinutes()` | Every 5 minutes |
| `everyFifteenMinutes()` | Every 15 minutes |
| `everyThirtyMinutes()` | Every 30 minutes |
| `hourly()` | Every hour |
| `hourlyAt(int $minute)` | At minute of every hour |
| `daily()` | Every day at midnight |
| `dailyAt(string $time)` | Every day at time (HH:MM) |
| `weekly()` | Every week (Monday 00:00) |
| `monthly()` | Every month (1st 00:00) |
| `at(string $time)` | Set run time |
| `withoutOverlapping()` | Prevent overlapping runs |
| `environments(array $envs)` | Restrict to environments |
| `run(string $basePath)` | Execute due tasks |
