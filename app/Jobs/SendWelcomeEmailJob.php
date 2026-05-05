<?php

declare(strict_types=1);

namespace App\Jobs;

/**
 * Example job to send welcome email.
 *
 * Usage:
 *   Queue::push(SendWelcomeEmailJob::class, ['email' => 'user@example.com', 'name' => 'John']);
 *
 * @package App\Jobs
 */
final class SendWelcomeEmailJob
{
    /**
     * Execute the job.
     *
     * @param array<string, mixed> $data Job data
     */
    public function handle(array $data = []): void
    {
        $email = $data['email'] ?? '';
        $name = $data['name'] ?? 'User';

        if ($email === '') {
            throw new \RuntimeException('Email address is required');
        }

        // Create mail instance
        $mail = \Siro\Core\Mail::to($email)
            ->subject("Welcome, {$name}!")
            ->html((new \App\Mails\WelcomeMail())->build(['name' => $name]));

        // Send email
        $mail->send();
    }
}
