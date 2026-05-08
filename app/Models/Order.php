<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

/**
 * Order model.
 *
 * @property int $id
 * @property string $customer_name
 * @property string $customer_email
 * @property float $total
 * @property string $status
 * @property string $items
 * @property string $created_at
 * @property string|null $updated_at
 */
final class Order extends Model
{
    protected string $table = 'orders';

    protected array $casts = [
        'id' => 'int',
        'total' => 'float',
    ];

    protected array $fillable = [
        'customer_name',
        'customer_email',
        'total',
        'status',
        'items',
    ];
}
