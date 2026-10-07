<?php

declare(strict_types=1);

namespace App;

final class Role
{
    public const ADMIN = 'admin';
    public const PLATFORM_ADMIN = 'platform_admin';
    public const USER = 'user';

    public static function isAdmin(string $role): bool
    {
        return in_array($role, [self::ADMIN, self::PLATFORM_ADMIN], true);
    }
}
