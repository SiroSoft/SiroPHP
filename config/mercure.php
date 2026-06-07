<?php

declare(strict_types=1);

use Siro\Core\Env;

return [
    'hub_url' => Env::get('MERCURE_HUB_URL', 'http://localhost:3001/.well-known/mercure'),
    'jwt_secret' => Env::get('MERCURE_JWT_SECRET', ''),
    'publisher_jwt' => Env::get('MERCURE_PUBLISHER_JWT', ''),
    'subscriber_jwt' => Env::get('MERCURE_SUBSCRIBER_JWT', ''),
];
