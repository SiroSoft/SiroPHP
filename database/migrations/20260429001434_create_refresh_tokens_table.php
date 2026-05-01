<?php

declare(strict_types=1);

return new class
{
    public function up(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS refresh_tokens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            jti VARCHAR(64) NOT NULL UNIQUE,
            user_id BIGINT UNSIGNED NOT NULL,
            revoked TINYINT NOT NULL DEFAULT 0,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS refresh_tokens');
    }
};