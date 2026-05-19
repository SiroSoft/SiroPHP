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

/** @var \Siro\Core\Schedule $schedule */
$schedule->command('queue:work')->everyMinute();

$schedule->command('log:cleanup --days=30')->daily();
