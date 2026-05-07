<?php

declare(strict_types=1);

namespace App\Models;

use Siro\Core\Model;

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
