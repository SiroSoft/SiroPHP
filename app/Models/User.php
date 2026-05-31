<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property int $status
 * @property int $token_version
 * @property int $login_attempts
 * @property string|null $locked_until
 * @property string|null $avatar
 * @property string|null $phone
 * @property string|null $email_verified_at
 * @property string|null $verification_token
 * @property string|null $password_reset_token
 * @property string|null $password_reset_expires_at
 * @property string $created_at
 * @property string|null $updated_at
 */
final class User extends Model
{
    protected string $table = 'users';

    protected array $hidden = ['password'];

    protected array $casts = [
        'id' => 'int',
        'status' => 'int',
        'token_version' => 'int',
        'login_attempts' => 'int',
        'locked_until' => 'datetime',
        'email_verified_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'phone',
    ];
}
