<?php

declare(strict_types=1);

use Siro\Core\DB;

final class PostSeeder
{
    public function run(): void
    {
        $existingCount = DB::table('posts')->count();
        if ($existingCount > 0) {
            echo "  [SKIP] {$existingCount} posts already exist\n";
            return;
        }

        $owner = DB::table('users')->orderBy('id')->first();
        if ($owner === null) {
            echo "  [SKIP] No users found. Run UserSeeder first.\n";
            return;
        }
        $ownerId = (int) ($owner['id'] ?? 0);

        $posts = [
            [
                'title' => 'Getting Started with SiroPHP: Build Your First API in Minutes',
                'body' => 'SiroPHP ships with full CRUD scaffolding, JWT authentication, and request tracing out of the box. '
                    . 'Generate a complete REST API with a single command, then test it from the CLI without leaving your terminal. '
                    . 'This guide walks through installation, your first CRUD resource, and testing workflow.',
                'status' => 'published',
            ],
            [
                'title' => 'Debugging Production APIs with Request Replay',
                'body' => 'Every failed request in SiroPHP writes a structured trace with timing, SQL queries, and exceptions. '
                    . 'The replay workflow lets you reproduce the exact request, diff responses before and after a fix, '
                    . 'and turn real failures into regression tests. No more guessing from scattered logs.',
                'status' => 'published',
            ],
            [
                'title' => 'JWT Authentication and Token Revocation Done Right',
                'body' => 'Learn how SiroPHP handles access and refresh token rotation, per-token revocation via token versioning, '
                    . 'and brute-force protection with login attempt tracking. Includes best practices for storing tokens '
                    . 'in single-page applications.',
                'status' => 'published',
            ],
            [
                'title' => 'From SQLite to MySQL: Scaling Your SiroPHP Application',
                'body' => 'Start fast with SQLite for development, then switch to MySQL or MariaDB for production with a single '
                    . 'environment change. Migrations, seeders, and the query builder work identically across drivers. '
                    . 'This post covers connection tuning, indexing, and slow-query analysis.',
                'status' => 'published',
            ],
            [
                'title' => 'Background Jobs Without the Headache: DB Queues Explained',
                'body' => 'SiroPHP includes a database-backed queue with exponential backoff, priorities, and failed-job retries. '
                    . 'No Redis required to start. This tutorial shows how to dispatch welcome emails, process uploads, '
                    . 'and monitor the queue from the command line.',
                'status' => 'draft',
            ],
            [
                'title' => 'Securing Your API: Rate Limiting, CORS, and Security Headers',
                'body' => 'A practical checklist for hardening a SiroPHP API before launch: per-route throttling, strict CORS '
                    . 'allow-lists, security headers middleware, input validation with form requests, and audit logging '
                    . 'for sensitive operations.',
                'status' => 'draft',
            ],
        ];

        $now = date('Y-m-d H:i:s');
        $inserted = 0;

        foreach ($posts as $post) {
            DB::table('posts')->insert([
                'user_id' => $ownerId,
                'title' => $post['title'],
                'body' => $post['body'],
                'status' => $post['status'],
                'locale' => 'en',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        echo "  Created {$inserted} posts\n";
    }
}
