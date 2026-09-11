<?php

declare(strict_types=1);

use Siro\Core\DB;

/**
 * Seeds a read-only demo user for public demos (admin-next/nuxt 1-click login).
 *
 * Demo credentials: DEMO_EMAIL / DEMO_PASSWORD env (defaults below, override in .env).
 * The demo user has role "viewer" and is blocked from writes by DemoGuard middleware.
 * Safe to re-run: skips if the demo email already exists.
 */
final class DemoSeeder
{
    public function run(): void
    {
        $email = getenv('DEMO_EMAIL');
        if ($email === false || $email === '') {
            $email = 'demo@skeleton.sirophp.com';
        }
        $password = getenv('DEMO_PASSWORD');
        if ($password === false || $password === '') {
            $password = 'Demo123!';
        }

        if (strlen($password) < 8) {
            echo "  [SKIP] DEMO_PASSWORD must be at least 8 characters\n";
            return;
        }

        $existing = DB::table('users')->where('email', $email)->first();
        if ($existing) {
            echo "  [SKIP] Demo {$email} already exists\n";
            return;
        }

        DB::table('users')->insert([
            'name' => 'Demo Viewer',
            'email' => $email,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'status' => 1,
            'token_version' => 1,
            // admin so read endpoints pass RBAC; writes still blocked
            // by DemoGuardMiddleware via the demo email match.
            'role' => 'admin',
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        echo "  Created demo user: {$email}\n";
    }
}
