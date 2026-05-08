<?php

declare(strict_types=1);

use Siro\Core\Env;

return [
    'allowed_origins' => explode(',', Env::get('CORS_ALLOWED_ORIGINS', '*')),
    'allowed_methods' => explode(',', Env::get('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
    'allowed_headers' => explode(',', Env::get('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With')),
    'max_age' => 86400,
];
