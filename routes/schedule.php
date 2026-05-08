<?php

declare(strict_types=1);

/**
 * Scheduled task definitions.
 *
 * Add the following line to your crontab to run every minute:
 *
 *   * * * * * cd /path/to/project && php siro schedule:run
 *
 * @package App
 */

$schedule->command('queue:work')->everyMinute();

$schedule->call(function () {
    $logDir = __DIR__ . '/../storage/logs/traces';
    foreach (glob($logDir . '/*.json') ?: [] as $file) {
        if (filemtime($file) < time() - 86400 * 7) {
            @unlink($file);
        }
    }
})->hourly();

$schedule->command('log:cleanup --days=30')->daily();
