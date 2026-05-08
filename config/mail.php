<?php

declare(strict_types=1);

use Siro\Core\Env;

return [
    'driver' => Env::get('MAIL_DRIVER', 'sendmail'),
    'from_address' => Env::get('MAIL_FROM_ADDRESS', 'noreply@localhost'),
    'from_name' => Env::get('MAIL_FROM_NAME', 'Siro API'),
    'smtp' => [
        'host' => Env::get('MAIL_HOST', ''),
        'port' => (int) Env::get('MAIL_PORT', '587'),
        'username' => Env::get('MAIL_USERNAME', ''),
        'password' => Env::get('MAIL_PASSWORD', ''),
    ],
];
