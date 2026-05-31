<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $color
 * @property string|null $description
 * @property int $is_active
 * @property int $sort_order
 * @property int|null $parent_id
 * @property string $created_at
 * @property string|null $updated_at
 */
final class Category extends Model
{
    protected string $table = 'categories';

    protected array $hidden = [];

    protected array $casts = [
        'id' => 'int',
        'is_active' => 'int',
        'sort_order' => 'int',
        'parent_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $fillable = [
        'name',
        'is_active',
        'color',
        'description',
        'sort_order',
        'parent_id',
        'created_at',
    ];
}
