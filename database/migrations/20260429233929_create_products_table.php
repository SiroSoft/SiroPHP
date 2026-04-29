<?php

declare(strict_types=1);

return new class {
    public function up(\PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS products (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=INNODB DEFAULT CHARSET=utf8mb4");
    }

    public function down(\PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS products");
    }
};
