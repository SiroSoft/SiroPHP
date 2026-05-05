<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User as UserModel;

/**
 * User service layer.
 *
 * Provides business logic operations on User models,
 * such as token version incrementation for logout.
 *
 * @package App\Services
 */
final class User
{
    public static function incrementTokenVersion(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $user = UserModel::find($userId);
        if ($user === null) {
            return false;
        }

        return $user->update(['token_version' => ($user->token_version ?? 0) + 1]) > 0;
    }
}