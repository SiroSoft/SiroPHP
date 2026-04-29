<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * User model.
 *
 * Represents the users table with hidden password field,
 * integer casts, and mass-assignment fillable fields.
 *
 * @package App\Models
 */
final class User extends Model
{
    protected string $table = 'users';

    /** @var array<int, string> */
    protected array $hidden = ['password'];

    /** @var array<string, string> */
    protected array $casts = [
        'id' => 'int',
        'status' => 'int',
        'token_version' => 'int',
    ];

    /** @var array<int, string> */
    protected array $fillable = [
        'name',
        'email',
        'password',
        'status',
        'token_version',
        'email_verified_at',
        'verification_token',
        'password_reset_token',
        'password_reset_expires_at',
    ];
}
