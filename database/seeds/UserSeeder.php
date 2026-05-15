<?php

declare(strict_types=1);

use Siro\Core\DB;

final class UserSeeder
{
    public function run(): void
    {
        $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@siro.dev';
        $adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin1234';

        if ($adminPassword !== getenv('ADMIN_PASSWORD') ?: 'admin1234') {
            // Env var was set and overridden
        }

        if (strlen($adminPassword) < 8) {
            echo "  [SKIP] ADMIN_PASSWORD must be at least 8 characters\n";
            return;
        }

        $password = password_hash($adminPassword, PASSWORD_BCRYPT);

        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => $adminEmail,
            'password' => $password,
            'status' => 1,
            'token_version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        echo "  Created admin user: {$adminEmail}\n";
        echo "  Set ADMIN_EMAIL and ADMIN_PASSWORD in .env to override defaults.\n";
    }
}
