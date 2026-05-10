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
    /** @param array<string, mixed> $data */
    public function handle(array $data = []): void
    {
        $email = (string) ($data['email'] ?? '');
        $name = (string) ($data['name'] ?? 'User');

        if ($email === '') {
            return;
        }

        $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = '<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; padding: 20px;">
    <h1>Welcome, ' . $safeName . '!</h1>
    <p>Thank you for registering. We\'re excited to have you on board.</p>
</body>
</html>';

        Mail::to($email)
            ->subject('Welcome to our platform!')
            ->html($html)
            ->send();
    }
}
