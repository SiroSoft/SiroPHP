<?php

declare(strict_types=1);

namespace App\Services;

<<<<<<< HEAD
use App\Models\User as UserModel;

/**
 * User service layer.
 *
 * Provides business logic operations on User models,
 * such as token version incrementation for logout.
 *
 * @package App\Services
 */
=======
use Siro\Core\Cache;
use Siro\Core\Database;

>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
final class User
{
    public static function incrementTokenVersion(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

<<<<<<< HEAD
        $user = UserModel::find($userId);
        if ($user === null) {
            return false;
        }

        return $user->update(['token_version' => ($user->token_version ?? 0) + 1]) > 0;
    }
}
=======
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
>>>>>>> 6869b98480a3897ddf17ae968422a43c371737f0
