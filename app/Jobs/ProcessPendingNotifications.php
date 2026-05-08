<?php

declare(strict_types=1);

namespace App\Jobs;

use Siro\Core\Database;

/**
 * Process pending notifications from the database.
 *
 * Demonstrates a job that reads from DB, processes items,
 * and sends emails. Run via: php siro queue:work
 *
 * @package App\Jobs
 */
final class ProcessPendingNotifications
{
    public function handle(array $data = []): void
    {
        $notifications = Database::select(
            "SELECT * FROM notifications WHERE sent_at IS NULL LIMIT 50"
        );

        foreach ($notifications as $notification) {
            $email = $notification['email'] ?? '';
            $subject = $notification['subject'] ?? 'Notification';
            $body = $notification['body'] ?? 'You have a new notification.';

            if ($email !== '') {
                \Siro\Core\Mail::to($email)
                    ->subject($subject)
                    ->html('<p>' . htmlspecialchars($body) . '</p>')
                    ->queue();
            }
        }
    }
}
