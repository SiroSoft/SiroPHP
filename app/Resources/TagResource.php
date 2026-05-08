<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

/**
 * Tag API resource transformer.
 */
final class TagResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? 0,
            'name' => $this->data['name'] ?? '',
            'created_at' => $this->data['created_at'] ?? '',
        ];
    }
}
