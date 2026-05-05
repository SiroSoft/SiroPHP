<?php

declare(strict_types=1);

use Siro\Core\Env;

/**
 * Database configuration.
 *
 * Returns an array of connection settings used by Database::configure().
 * Driver-aware default ports: MySQL=3306, PostgreSQL=5432, SQLite=0.
 *
 * @return array<string, mixed>
 */

$driver = Env::get('DB_CONNECTION', 'mysql');

$defaultPorts = [
    'mysql' => 3306,
    'mariadb' => 3306,
    'pgsql' => 5432,
    'postgres' => 5432,
    'postgresql' => 5432,
    'sqlite' => 0,
];

return [
    'driver' => $driver,
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => (int) Env::get('DB_PORT', (string) ($defaultPorts[$driver] ?? 3306)),
    'database' => Env::get('DB_DATABASE', ''),
    'username' => Env::get('DB_USERNAME', ''),
    'password' => Env::get('DB_PASSWORD', ''),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    'slow_query_threshold' => (int) Env::get('DB_SLOW_QUERY_THRESHOLD', '100'),
];
