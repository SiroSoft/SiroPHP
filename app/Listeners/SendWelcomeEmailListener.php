<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreatedEvent;
use Siro\Core\Queue;
use App\Jobs\SendWelcomeEmail;

final class SendWelcomeEmailListener
{
    public static function register(): void
    {
        UserCreatedEvent::listen(function (array $payload): void {
            $email = $payload['email'] ?? '';
            $name = $payload['name'] ?? 'User';

            if ($email !== '') {
                Queue::registerJob(SendWelcomeEmail::class);
                Queue::push(SendWelcomeEmail::class, [
                    'email' => $email,
                    'name' => $name,
                ]);
            }
        });
    }
}
