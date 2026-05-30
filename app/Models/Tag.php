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
 * @property string $created_at
 * @property string|null $updated_at
 */
final class Tag extends Model
{
    protected string $table = 'tags';

    protected array $hidden = [];

    protected array $casts = [
        'id' => 'int',
        'is_active' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $fillable = [
        'name',
    ];
}
