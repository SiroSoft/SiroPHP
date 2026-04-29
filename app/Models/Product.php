<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

final class Product extends Model
{
    protected string $table = 'productses';

    protected array $hidden = [];

    protected array $casts = [
        'id' => 'int',
    ];

    protected array $fillable = [
        'name',
    ];
}
