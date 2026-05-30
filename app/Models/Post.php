<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property string|null $image
 * @property string $locale
 * @property string $status
 * @property int $user_id
 * @property int|null $category_id
 * @property string|null $excerpt
 * @property string $created_at
 * @property string|null $updated_at
 */
final class Post extends Model
{
    protected string $table = 'posts';

    protected array $hidden = [];

    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'category_id' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $fillable = [
        'title',
        'body',
        'image',
        'locale',
        'status',
        'user_id',
        'category_id',
        'excerpt',
    ];
}
