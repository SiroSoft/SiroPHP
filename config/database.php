<?php

declare(strict_types=1);

use Siro\Core\Env;

return [
    'driver' => Env::get('DB_CONNECTION', 'mysql'),
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => (int) Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_DATABASE', ''),
    'username' => Env::get('DB_USERNAME', ''),
    'password' => Env::get('DB_PASSWORD', ''),
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
];
