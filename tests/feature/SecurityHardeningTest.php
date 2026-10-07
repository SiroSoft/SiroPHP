<?php

declare(strict_types=1);

namespace App\Tests\Feature;

use App\Resources\UserResource;
use App\Tests\TestCase;
use App\Role;
use Siro\Core\Request;
use App\Exceptions\Handler;

final class SecurityHardeningTest extends TestCase
{
    public function testAdminProjectionOmitsContactPii(): void
    {
        $data = UserResource::admin([
            'id' => 7,
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'phone' => '+15555555555',
            'role' => Role::ADMIN,
            'status' => 1,
        ]);

        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('phone', $data);
        $this->assertSame(Role::ADMIN, $data['role']);
    }

    public function testUnexpectedErrorsHaveStablePublicMessage(): void
    {
        $_ENV['APP_DEBUG'] = 'true';
        putenv('APP_DEBUG=true');

        $request = new Request('GET', '/', [], [], [], '127.0.0.1');
        $response = Handler::handle(new \RuntimeException('provider secret: db://user:pass@host'), $request);
        $payload = $response->payload();

        $this->assertSame(500, $response->statusCode());
        $this->assertSame('Internal Server Error', $payload['message'] ?? null);
        $this->assertStringNotContainsString('provider secret', json_encode($payload) ?: '');
    }

    public function testPlatformAdminRoleIsExplicit(): void
    {
        $this->assertFalse(Role::isAdmin(Role::USER));
        $this->assertTrue(Role::isAdmin(Role::ADMIN));
        $this->assertTrue(Role::isAdmin(Role::PLATFORM_ADMIN));
    }
}
