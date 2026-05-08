<?php

declare(strict_types=1);

use Siro\Core\Env;

return [
    'secret' => Env::get('JWT_SECRET', ''),
    'ttl' => (int) Env::get('JWT_TTL', '3600'),
    'refresh_ttl' => (int) Env::get('JWT_REFRESH_TTL', '604800'),
    'algorithm' => Env::get('JWT_ALGORITHM', 'HS256'),
];
