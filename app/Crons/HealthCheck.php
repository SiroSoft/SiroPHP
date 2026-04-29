<?php

declare(strict_types=1);

namespace App\Crons;

/**
 * Example cron job class.
 *
 * Register in routes/schedule.php:
 *   $schedule->call([HealthCheck::class, 'run'])->hourly();
 *
 * @package App\Crons
 */
final class HealthCheck
{
    /**
     * Perform a health check on the application.
     *
     * Verifies database connectivity and logs the result.
     */
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
