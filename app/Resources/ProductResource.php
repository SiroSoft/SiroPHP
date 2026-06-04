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
                if (is_array($cat) && isset($cat['id']) && is_numeric($cat['id'])) {
                    $categoryId = (int) $cat['id'];
                }
            } catch (\Throwable) {
            }
        }

        return [
            'id' => $this->data['id'] ?? null,
            'name' => is_string($this->data['name'] ?? null) ? htmlspecialchars($this->data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['name'] ?? null),
            'description' => is_string($this->data['description'] ?? null) ? htmlspecialchars($this->data['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['description'] ?? null),
            'price' => isset($this->data['price']) && is_numeric($this->data['price']) ? (float) $this->data['price'] : null,
            'compare_price' => isset($this->data['compare_price']) && is_numeric($this->data['compare_price']) ? (float) $this->data['compare_price'] : null,
            'cost_price' => isset($this->data['cost_price']) && is_numeric($this->data['cost_price']) ? (float) $this->data['cost_price'] : null,
            'barcode' => $this->data['barcode'] ?? null,
            'stock' => isset($this->data['stock']) && is_numeric($this->data['stock']) ? (int) $this->data['stock'] : null,
            'stock_min' => isset($this->data['stock_min']) && is_numeric($this->data['stock_min']) ? (int) $this->data['stock_min'] : null,
            'weight' => isset($this->data['weight']) && is_numeric($this->data['weight']) ? (float) $this->data['weight'] : null,
            'width' => isset($this->data['width']) && is_numeric($this->data['width']) ? (float) $this->data['width'] : null,
            'height' => isset($this->data['height']) && is_numeric($this->data['height']) ? (float) $this->data['height'] : null,
            'length' => isset($this->data['length']) && is_numeric($this->data['length']) ? (float) $this->data['length'] : null,
            'cover_image' => is_string($this->data['cover_image'] ?? null) ? htmlspecialchars($this->data['cover_image'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['cover_image'] ?? null),
            'short_description' => is_string($this->data['short_description'] ?? null) ? htmlspecialchars($this->data['short_description'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['short_description'] ?? null),
            'sku' => null,
            'slug' => null,
            'category_id' => $categoryId,
            'category_name' => is_string($dbCategory) ? htmlspecialchars($dbCategory, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $dbCategory,
            'is_active' => is_string($status) ? $status === 'active' : true,
            'is_featured' => isset($this->data['is_featured']) ? (bool) $this->data['is_featured'] : false,
            'created_at' => $this->data['created_at'] ?? null,
            'updated_at' => $this->data['updated_at'] ?? null,
        ];
    }
}
