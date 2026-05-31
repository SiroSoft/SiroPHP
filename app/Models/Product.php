<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property float $price
 * @property int $stock
 * @property string $category
 * @property string $status
 * @property int $user_id
 * @property string|null $cover_image
 * @property string|null $short_description
 * @property string $created_at
 * @property string|null $updated_at
 */
final class Product extends Model
{
    protected string $table = 'products';

    protected array $hidden = [];

    protected array $casts = [
        'id' => 'int',
        'price' => 'float',
        'stock' => 'int',
        'user_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'category',
        'status',
        'user_id',
        'cover_image',
        'short_description',
    ];
}
