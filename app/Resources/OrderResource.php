<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class OrderResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? 0,
            'customer_name' => $this->data['customer_name'] ?? '',
            'customer_email' => $this->data['customer_email'] ?? '',
            'total' => (float) ($this->data['total'] ?? 0),
            'status' => $this->data['status'] ?? 'pending',
            'items' => $this->data['items'] ?? '[]',
            'created_at' => $this->data['created_at'] ?? '',
            'updated_at' => $this->data['updated_at'] ?? '',
        ];
    }
}
