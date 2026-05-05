<?php

declare(strict_types=1);

return new class {
    public function up(PDO $db): void
    {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'sqlite') {
            $db->exec(
                'CREATE TABLE IF NOT EXISTS products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    price DECIMAL(10,2) NOT NULL DEFAULT 0,
                    stock INTEGER NOT NULL DEFAULT 0,
                    category VARCHAR(100),
                    status VARCHAR(20) NOT NULL DEFAULT "active",
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                )'
            );
        } else {
            $db->exec(
                'CREATE TABLE IF NOT EXISTS products (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    price DECIMAL(10,2) NOT NULL DEFAULT 0,
                    stock INT NOT NULL DEFAULT 0,
                    category VARCHAR(100),
                    status VARCHAR(20) NOT NULL DEFAULT "active",
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        }
    }

    public function down(PDO $db): void
    {
        $db->exec('DROP TABLE IF EXISTS products');
    }
};
