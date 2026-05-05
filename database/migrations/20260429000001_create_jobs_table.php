<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $db): void
    {
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS jobs (
                    id BIGSERIAL PRIMARY KEY,
                    job VARCHAR(255) NOT NULL,
                    data TEXT NOT NULL DEFAULT '',
                    attempts INT NOT NULL DEFAULT 0,
                    max_attempts INT NOT NULL DEFAULT 3,
                    priority INT NOT NULL DEFAULT 0,
                    timeout INT NOT NULL DEFAULT 120,
                    available_at BIGINT NOT NULL DEFAULT 0,
                    locked_until BIGINT DEFAULT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_jobs_available ON jobs (available_at, locked_until)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_jobs_priority ON jobs (priority DESC, id ASC)");

            $db->exec("
                CREATE TABLE IF NOT EXISTS failed_jobs (
                    id BIGSERIAL PRIMARY KEY,
                    job VARCHAR(255) NOT NULL,
                    data TEXT NOT NULL DEFAULT '',
                    error TEXT NOT NULL DEFAULT '',
                    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } elseif ($driver === 'sqlite') {
            $db->exec("
                CREATE TABLE IF NOT EXISTS jobs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    job TEXT NOT NULL,
                    data TEXT NOT NULL DEFAULT '',
                    attempts INTEGER NOT NULL DEFAULT 0,
                    max_attempts INTEGER NOT NULL DEFAULT 3,
                    priority INTEGER NOT NULL DEFAULT 0,
                    timeout INTEGER NOT NULL DEFAULT 120,
                    available_at INTEGER NOT NULL DEFAULT 0,
                    locked_until INTEGER DEFAULT NULL,
                    created_at TEXT NOT NULL DEFAULT (datetime('now'))
                )
            ");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_jobs_available ON jobs (available_at, locked_until)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_jobs_priority ON jobs (priority DESC, id ASC)");

            $db->exec("
                CREATE TABLE IF NOT EXISTS failed_jobs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    job TEXT NOT NULL,
                    data TEXT NOT NULL DEFAULT '',
                    error TEXT NOT NULL DEFAULT '',
                    failed_at TEXT NOT NULL DEFAULT (datetime('now'))
                )
            ");
        } else {
            // MySQL / MariaDB
            $db->exec("
                CREATE TABLE IF NOT EXISTS jobs (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    job VARCHAR(255) NOT NULL,
                    data TEXT NOT NULL,
                    attempts INT NOT NULL DEFAULT 0,
                    max_attempts INT NOT NULL DEFAULT 3,
                    priority INT NOT NULL DEFAULT 0,
                    timeout INT NOT NULL DEFAULT 120,
                    available_at BIGINT NOT NULL DEFAULT 0,
                    locked_until BIGINT DEFAULT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_jobs_available (available_at, locked_until),
                    INDEX idx_jobs_priority (priority DESC, id ASC)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS failed_jobs (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    job VARCHAR(255) NOT NULL,
                    data TEXT NOT NULL,
                    error TEXT NOT NULL,
                    failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }

    public function down(\PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS failed_jobs");
        $db->exec("DROP TABLE IF EXISTS jobs");
    }
};
