<?php

declare(strict_types=1);

return new class
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL');
            $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS verification_token VARCHAR(64) NULL');
            $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(64) NULL');
            $pdo->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_expires_at TIMESTAMP NULL');
        } else {
            try { $pdo->exec("ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL"); } catch (Throwable) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(64) NULL"); } catch (Throwable) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) NULL"); } catch (Throwable) {}
            try { $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_expires_at TIMESTAMP NULL"); } catch (Throwable) {}
        }
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $pdo->exec('ALTER TABLE users DROP COLUMN IF EXISTS email_verified_at');
            $pdo->exec('ALTER TABLE users DROP COLUMN IF EXISTS verification_token');
            $pdo->exec('ALTER TABLE users DROP COLUMN IF EXISTS password_reset_token');
            $pdo->exec('ALTER TABLE users DROP COLUMN IF EXISTS password_reset_expires_at');
        } else {
            try { $pdo->exec('ALTER TABLE users DROP COLUMN email_verified_at'); } catch (Throwable) {}
            try { $pdo->exec('ALTER TABLE users DROP COLUMN verification_token'); } catch (Throwable) {}
            try { $pdo->exec('ALTER TABLE users DROP COLUMN password_reset_token'); } catch (Throwable) {}
            try { $pdo->exec('ALTER TABLE users DROP COLUMN password_reset_expires_at'); } catch (Throwable) {}
        }
    }
};
