<?php

declare(strict_types=1);

use Siro\Core\Database;

function db_driver(): string
{
    static $driver = null;
    if ($driver === null) {
        try {
            $driver = Database::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (\Throwable) {
            $driver = 'mysql';
        }
    }
    return $driver;
}

function db_id_col(): string
{
    return db_driver() === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
}

function db_type_int(): string
{
    return db_driver() === 'sqlite' ? 'INTEGER' : 'INT';
}

function db_now(): string
{
    return db_driver() === 'sqlite' ? "(datetime('now'))" : 'CURRENT_TIMESTAMP';
}

function db_datetime_col(string $default = 'now'): string
{
    $driver = db_driver();
    $col = $driver === 'sqlite' ? 'TEXT' : 'DATETIME';
    if ($default === 'now') {
        return $col . " NOT NULL DEFAULT " . db_now();
    }
    return $col . " DEFAULT NULL";
}

