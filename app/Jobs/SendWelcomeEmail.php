<?php

declare(strict_types=1);

namespace App\Jobs;

use Siro\Core\Mail;

/**
 * Send a welcome email to a newly registered user.
 *
 * Usage:
 *   Queue::push(SendWelcomeEmail::class, ['email' => 'user@example.com', 'name' => 'John']);
 *
 * @package App\Jobs
 */
final class SendWelcomeEmail
{
    public function handle(array $data = []): void
    {
        $email = (string) ($data['email'] ?? '');
        $name = (string) ($data['name'] ?? 'User');

        if ($email === '') {
            return;
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; padding: 20px;">
    <h1>Welcome, {$name}!</h1>
    <p>Thank you for registering. We're excited to have you on board.</p>
</body>
</html>
HTML;

        Mail::to($email)
            ->subject('Welcome to our platform!')
            ->html($html)
            ->send();
    }
}
