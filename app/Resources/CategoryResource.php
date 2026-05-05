<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class CategoryResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'name' => $this->data['name'] ?? null,
            'created_at' => $this->data['created_at'] ?? null,
        ];
    }
}
