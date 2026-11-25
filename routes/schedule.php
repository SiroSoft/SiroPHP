<?php

/**
 * Scheduled task definitions.
 *
 * Add the following line to your crontab to run every minute:
 *
 *   * * * * * cd /path/to/project && php siro schedule:run
 *
 * @package App
 */

declare(strict_types=1);

/** @var \Siro\Core\Schedule $schedule */
$schedule->command('queue:work')->everyMinute();

$schedule->command('log:cleanup --days=30')->daily();
