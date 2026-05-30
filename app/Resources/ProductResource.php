<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class ProductResource extends Resource
{
    public function toArray(): array
    {
        $status = $this->data['status'] ?? null;
        $dbCategory = $this->data['category'] ?? null;

        $categoryId = null;
        if (is_string($dbCategory) && $dbCategory !== '') {
            try {
                $cat = \Siro\Core\Database::first("SELECT id FROM categories WHERE name = ? LIMIT 1", [$dbCategory]);
                if (is_array($cat) && isset($cat['id']) && is_numeric($cat['id'])) $categoryId = (int) $cat['id'];
            } catch (\Throwable) {}
        }

        return [
            'id' => $this->data['id'] ?? null,
            'name' => is_string($this->data['name'] ?? null) ? htmlspecialchars($this->data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['name'] ?? null),
            'description' => is_string($this->data['description'] ?? null) ? htmlspecialchars($this->data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['description'] ?? null),
            'price' => isset($this->data['price']) && is_numeric($this->data['price']) ? (float) $this->data['price'] : null,
            'stock' => isset($this->data['stock']) && is_numeric($this->data['stock']) ? (int) $this->data['stock'] : null,
            'cover_image' => is_string($this->data['cover_image'] ?? null) ? htmlspecialchars($this->data['cover_image'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['cover_image'] ?? null),
            'short_description' => is_string($this->data['short_description'] ?? null) ? htmlspecialchars($this->data['short_description'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['short_description'] ?? null),
            'sku' => null,
            'slug' => null,
            'category_id' => $categoryId,
            'category_name' => is_string($dbCategory) ? htmlspecialchars($dbCategory, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $dbCategory,
            'is_active' => is_string($status) ? $status === 'active' : true,
            'is_featured' => false,
            'created_at' => $this->data['created_at'] ?? null,
            'updated_at' => $this->data['updated_at'] ?? null,
        ];
    }
}
