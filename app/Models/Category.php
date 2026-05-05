<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

final class Category extends Model
{
    protected string $table = 'categories';

    protected array $hidden = [];

    protected array $casts = [
        'id' => 'int',
    ];

    protected array $fillable = [
        'name',
    ];
}
