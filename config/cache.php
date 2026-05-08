<?php

declare(strict_types=1);

use Siro\Core\Env;

return [
    'driver' => Env::get('CACHE_DRIVER', 'file'),
    'prefix' => Env::get('CACHE_PREFIX', 'siro:'),
    'ttl' => (int) Env::get('CACHE_TTL', '60'),
    'redis' => [
        'host' => Env::get('REDIS_HOST', '127.0.0.1'),
        'port' => (int) Env::get('REDIS_PORT', '6379'),
        'password' => Env::get('REDIS_PASSWORD', ''),
        'database' => (int) Env::get('REDIS_DB', '0'),
        'timeout' => (float) Env::get('REDIS_TIMEOUT', '0.2'),
    ],
];
