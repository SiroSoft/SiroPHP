<?php

declare(strict_types=1);

use Siro\Core\DB;

final class UserSeeder
{
    public function run(): void
    {
        $adminEmail = (string) (\Siro\Core\Env::get('ADMIN_EMAIL', 'admin@example.com'));
        $adminPassword = (string) (\Siro\Core\Env::get('ADMIN_PASSWORD', 'password'));
        $password = password_hash($adminPassword, PASSWORD_DEFAULT);

        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => $adminEmail,
            'password' => $password,
            'status' => 1,
            'token_version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        echo "  Created admin user: {$adminEmail} / (configured password)\n";
    }
}
