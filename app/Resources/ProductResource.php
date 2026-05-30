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
        $status = $this->data['status'] ?? null;
        $dbCategory = $this->data['category'] ?? null;
        return [
            'id' => $this->data['id'] ?? null,
            'name' => is_string($this->data['name'] ?? null) ? htmlspecialchars($this->data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['name'] ?? null),
            'description' => is_string($this->data['description'] ?? null) ? htmlspecialchars($this->data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['description'] ?? null),
            'price' => $this->data['price'] ?? null,
            'stock' => $this->data['stock'] ?? null,
            'cover_image' => $this->data['cover_image'] ?? null,
            'short_description' => $this->data['short_description'] ?? null,
            'sku' => null,
            'slug' => null,
            'category_name' => is_string($dbCategory) ? htmlspecialchars($dbCategory, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $dbCategory,
            'is_active' => is_string($status) ? $status === 'active' : true,
            'is_featured' => false,
            'created_at' => $this->data['created_at'] ?? null,
            'updated_at' => is_string($this->data['updated_at'] ?? null) ? htmlspecialchars($this->data['updated_at'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['updated_at'] ?? null),
        ];
    }
}
