<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class ProductResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'name' => $this->data['name'] ?? null,
            'description' => $this->data['description'] ?? null,
            'price' => $this->data['price'] ?? null,
            'stock' => $this->data['stock'] ?? null,
            'category' => $this->data['category'] ?? null,
            'status' => $this->data['status'] ?? null,
            'created_at' => $this->data['created_at'] ?? null,
        ];
    }
}
