<?php

declare(strict_types=1);

use Siro\Core\Env;

return [
    'name' => Env::get('APP_NAME', 'Siro API Framework'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'locale' => Env::get('APP_LOCALE', 'en'),
    'fallback_locale' => Env::get('APP_FALLBACK_LOCALE', 'en'),
    'trusted_proxies' => Env::get('APP_TRUSTED_PROXIES', ''),
    'url' => Env::get('APP_URL', 'http://localhost:8080'),
];
