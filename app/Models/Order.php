<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * @property int $id
 * @property string $customer_name
 * @property string $customer_email
 * @property float $total
 * @property string $status
 * @property string $items
 * @property int $user_id
 * @property string $created_at
 * @property string|null $updated_at
 */
final class Order extends Model
{
    protected string $table = 'orders';

    protected array $hidden = [];

    protected array $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'total' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $fillable = [
        'customer_name',
        'customer_email',
        'items',
        'user_id',
        'total',
        'status',
    ];
}
