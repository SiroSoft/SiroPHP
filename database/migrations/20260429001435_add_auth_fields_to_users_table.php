<?php

declare(strict_types=1);

return new class
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $null = $driver === 'pgsql' ? 'NULL' : 'NULL';
        $timestamp = $driver === 'pgsql' ? 'TIMESTAMP' : 'TIMESTAMP';

        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at {$timestamp} {$null}");
        } catch (Throwable) {}

        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(64) {$null}");
        } catch (Throwable) {}

        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) {$null}");
        } catch (Throwable) {}

        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_expires_at {$timestamp} {$null}");
        } catch (Throwable) {}
    }

    public function down(PDO $pdo): void
    {
        try { $pdo->exec('ALTER TABLE users DROP COLUMN email_verified_at'); } catch (Throwable) {}
        try { $pdo->exec('ALTER TABLE users DROP COLUMN verification_token'); } catch (Throwable) {}
        try { $pdo->exec('ALTER TABLE users DROP COLUMN password_reset_token'); } catch (Throwable) {}
        try { $pdo->exec('ALTER TABLE users DROP COLUMN password_reset_expires_at'); } catch (Throwable) {}
    }
};