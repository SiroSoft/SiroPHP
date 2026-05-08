<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class OrderResource extends Resource
{
    public function toArray(): array
    {
        $items = $this->data['items'] ?? null;
        if (is_string($items)) {
            $items = json_decode($items, true) ?? [];
        }
        if (!is_array($items)) {
            $items = [];
        }

        return [
            'id' => (int) ($this->data['id'] ?? 0),
            'customer_name' => (string) ($this->data['customer_name'] ?? ''),
            'customer_email' => (string) ($this->data['customer_email'] ?? ''),
            'total' => (float) ($this->data['total'] ?? 0),
            'status' => (string) ($this->data['status'] ?? 'pending'),
            'items' => $items,
            'created_at' => (string) ($this->data['created_at'] ?? ''),
            'updated_at' => (string) ($this->data['updated_at'] ?? ''),
        ];
    }
}
