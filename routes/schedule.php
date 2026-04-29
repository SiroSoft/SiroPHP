<?php

declare(strict_types=1);

/**
 * Scheduled task definitions.
 *
 * This file is loaded by: php siro schedule:run
 * Add the following line to your crontab to run every minute:
 *
 *   * * * * * cd /path/to/project && php siro schedule:run
 *
 * Available scheduling methods:
 *   ->everyMinute()           Every minute
 *   ->hourly()                Every hour (minute 0)
 *   ->daily()                 00:00 daily
 *   ->dailyAt('06:30')        06:30 daily
 *   ->weekly()                Sunday 00:00
 *   ->monthly()               First day 00:00
 *   ->cron('0 6 * * 1')       Custom cron expression (Monday 06:00)
 *
 * @package App
 */

/*
 * === EXAMPLES ===
 *
 * // Run a CLI command daily
 * // (command passed to: php siro db:seed UserSeeder)
 * $schedule->command('db:seed UserSeeder')->daily();
 *
 * // Run a closure every hour
 * $schedule->call(function () {
 *     $logDir = __DIR__ . '/../storage/logs/traces';
 *     foreach (glob($logDir . '/*.json') ?: [] as $file) {
 *         if (filemtime($file) < time() - 86400 * 7) {
 *             @unlink($file);
 *         }
 *     }
 * })->hourly();
 *
 * // Custom cron: Monday 06:00
 * // $schedule->command('report:weekly')->cron('0 6 * * 1');
 *
 * // Call a static method on a class
 * // app/Crons/HealthCheck.php
 * // $schedule->call([\App\Crons\HealthCheck::class, 'run'])->hourly();
 */
