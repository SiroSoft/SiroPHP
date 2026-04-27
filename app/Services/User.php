<?php

declare(strict_types=1);

namespace App\Services;

use Siro\Core\Cache;
use Siro\Core\Database;

final class User
{
    public static function incrementTokenVersion(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $affected = Database::execute(
            'UPDATE users SET token_version = token_version + 1 WHERE id = :id',
            ['id' => $userId]
        );

        if ($affected > 0) {
            Cache::flushQueryBuilderTable('users');
        }

        return $affected > 0;
    }
}
