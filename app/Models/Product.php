<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

final class Product extends Model
{
    protected string $table = 'products';

    protected array $hidden = [];

    protected array $casts = [
        'id' => 'int',
        'price' => 'float',
        'stock' => 'int',
    ];

    protected array $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'category',
        'status',
    ];
}
