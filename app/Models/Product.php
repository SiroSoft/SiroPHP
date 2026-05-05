<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * Product model.
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property float $price
 * @property int $stock
 * @property string $category
 * @property string $status
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
