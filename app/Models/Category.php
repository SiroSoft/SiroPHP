<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * Category model.
 *
 * @property int $id
 * @property string $name
 * @property string $created_at
 *
 * @package App\Models
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
    ];

    protected array $fillable = [
        'name',
        'is_active',
        'color',
        'description',
        'sort_order',
        'parent_id',
    ];
}
