<?php

declare(strict_types=1);

use Siro\Core\Database;

return new class {
    public function up(): void
    {
        $driver = 'mysql';
        try {
            $driver = Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable) {
        }

        if (in_array($driver, ['mysql', 'pgsql', 'postgres', 'postgresql'], true)) {
            $sql = match ($driver) {
                'pgsql', 'postgres', 'postgresql' => 'ALTER TABLE refresh_tokens ALTER COLUMN user_id TYPE INTEGER USING user_id::integer',
                default => 'ALTER TABLE refresh_tokens MODIFY COLUMN user_id INTEGER NOT NULL',
            };
            Database::execute($sql);
        }
    }

    public function down(): void
    {
        $driver = 'mysql';
        try {
            $driver = Database::connection()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable) {
        }

        if (in_array($driver, ['mysql', 'pgsql', 'postgres', 'postgresql'], true)) {
            $sql = match ($driver) {
                'pgsql', 'postgres', 'postgresql' => 'ALTER TABLE refresh_tokens ALTER COLUMN user_id TYPE BIGINT USING user_id::bigint',
                default => 'ALTER TABLE refresh_tokens MODIFY COLUMN user_id BIGINT NOT NULL',
            };
            Database::execute($sql);
        }
    }
};
