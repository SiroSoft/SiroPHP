<?php

declare(strict_types=1);

namespace App\Jobs;

use Siro\Core\Database;

/**
 * Process pending notifications from the database.
 *
 * Requires a 'notifications' table: run the appropriate migration first.
 * Demonstrates a job that reads from DB, processes items, and sends emails.
 * Run via: php siro queue:work
 *
 * @package App\Jobs
 */
final class ProcessPendingNotifications
{
    /** @param array<string, mixed> $data */
    public function handle(array $data = []): void
    {
        try {
            $notifications = Database::select(
                "SELECT * FROM notifications WHERE sent_at IS NULL LIMIT 50"
            );
        } catch (\Throwable) {
            \Siro\Core\Logger::error(new \RuntimeException(
                'ProcessPendingNotifications: notifications table not found. '
                . 'Run migration to create it.'
            ));
            return;
        }

        foreach ($notifications as $notification) {
            /** @var array<string, mixed> $notification */
            $rawEmail = $notification['email'] ?? '';
            $rawSubject = $notification['subject'] ?? 'Notification';
            $rawBody = $notification['body'] ?? 'You have a new notification.';
            /** @var string $rawEmail */
            /** @var string $rawSubject */
            /** @var string $rawBody */
            $email = $rawEmail;
            $subject = $rawSubject;
            $body = $rawBody;

            if ($email !== '') {
                \Siro\Core\Mail::to($email)
                    ->subject($subject)
                    ->html('<p>' . htmlspecialchars($body) . '</p>')
                    ->queue();
            }
        }
    }
}
