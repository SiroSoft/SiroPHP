<?php

declare(strict_types=1);

namespace App\Mails;

/**
 * Welcome email template.
 *
 * Usage:
 *   Mail::to('user@example.com')
 *       ->subject('Welcome!')
 *       ->html((new WelcomeMail())->build(['name' => 'John']))
 *       ->send();
 *
 * @package App\Mails
 */
final class WelcomeMail
{
    /**
     * @param array<string, mixed> $data
     */
    public function build(array $data = []): string
    {
        $name = $data['name'] ?? 'User';

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: #4f46e5; padding: 20px; text-align: center;">
        <h1 style="color: white; margin: 0;">Welcome, {$name}!</h1>
    </div>
    <div style="padding: 20px; line-height: 1.6;">
        <p>Thank you for joining us. We're thrilled to have you on board.</p>
        <p>If you have any questions, feel free to reply to this email.</p>
    </div>
</body>
</html>
HTML;
    }
}
