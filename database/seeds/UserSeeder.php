<?php

declare(strict_types=1);

use Siro\Core\DB;

final class UserSeeder
{
    public function run(): void
    {
        $password = password_hash('password', PASSWORD_DEFAULT);

        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => $password,
            'status' => 1,
            'token_version' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        echo "  Created admin user: admin@example.com / password\n";
    }
}
