<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

final class Tag extends Model
{
    protected string $table = 'tags';

    protected array $casts = [
        'id' => 'int',
    ];

    protected array $fillable = [
        'name',
    ];
}
