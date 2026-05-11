<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

final class TestUnderscore extends Model
{
    protected string $table = 'test_underscores';

    /** @var array<int, string> */
    protected array $hidden = [];

    /** @var array<string, string> */
    protected array $casts = [
        'id' => 'int',
    ];

    /** @var array<int, string> */
    protected array $fillable = [];
}
