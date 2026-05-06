<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * Post model for blog/demo API.
 *
 * @property int $id
 * @property string $title
 * @property string $body
 * @property string|null $image
 * @property string $locale
 * @property string $status
 * @property string $created_at
 *
 * @package App\Models
 */
final class Post extends Model
{
    protected string $table = 'posts';

    protected array $casts = [
        'id' => 'int',
    ];

    protected array $fillable = [
        'title',
        'body',
        'image',
        'locale',
        'status',
    ];
}
