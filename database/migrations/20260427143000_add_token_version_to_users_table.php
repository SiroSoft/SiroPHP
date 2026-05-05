<?php

declare(strict_types=1);

return new class {
    public function up(PDO $db): void
    {
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS token_version INT NOT NULL DEFAULT 1');
            return;
        }

        try {
            $db->exec('ALTER TABLE users ADD COLUMN token_version INT NOT NULL DEFAULT 1');
        } catch (Throwable) {
            // column already exists
        }
    }

    public function down(PDO $db): void
    {
        $driver = (string) $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $db->exec('ALTER TABLE users DROP COLUMN IF EXISTS token_version');
            return;
        }

        try {
            $db->exec('ALTER TABLE users DROP COLUMN token_version');
        } catch (Throwable) {
            // column may not exist
        }
    }
};
