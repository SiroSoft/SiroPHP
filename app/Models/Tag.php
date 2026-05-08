<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * Tag model.
 *
 * @property int $id
 * @property string $name
 * @property string $created_at
 */
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
