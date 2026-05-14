<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

/**
 * Product resource transformer.
 *
 * @package App\Resources
 */

final class ProductResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'name' => is_string($this->data['name'] ?? null) ? htmlspecialchars($this->data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['name'] ?? null),
            'description' => is_string($this->data['description'] ?? null) ? htmlspecialchars($this->data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['description'] ?? null),
            'price' => $this->data['price'] ?? null,
            'stock' => $this->data['stock'] ?? null,
            'category' => is_string($this->data['category'] ?? null) ? htmlspecialchars($this->data['category'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['category'] ?? null),
            'status' => is_string($this->data['status'] ?? null) ? htmlspecialchars($this->data['status'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['status'] ?? null),
            'created_at' => $this->data['created_at'] ?? null,
        ];
    }
}
