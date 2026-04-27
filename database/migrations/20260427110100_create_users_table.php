<?php

declare(strict_types=1);

return new class {
    public function up(PDO $db): void
    {
        $db->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                status TINYINT(1) NOT NULL DEFAULT 1,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS users');
    }
};
