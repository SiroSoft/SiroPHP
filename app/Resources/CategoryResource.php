<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

/**
 * Category resource transformer.
 *
 * @package App\Resources
 */

final class CategoryResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'name' => is_string($this->data['name'] ?? null) ? htmlspecialchars($this->data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['name'] ?? null),
            'slug' => null,
            'description' => null,
            'color' => null,
            'icon' => null,
            'parent_id' => null,
            'sort_order' => 0,
            'is_active' => isset($this->data['is_active']) ? (bool) $this->data['is_active'] : true,
            'created_at' => $this->data['created_at'] ?? null,
            'updated_at' => is_string($this->data['updated_at'] ?? null) ? htmlspecialchars($this->data['updated_at'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['updated_at'] ?? null),
        ];
    }
}
