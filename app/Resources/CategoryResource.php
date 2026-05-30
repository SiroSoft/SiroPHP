<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class CategoryResource extends Resource
{
    public function toArray(): array
    {
        $d = $this->data;
        return [
            'id' => $d['id'] ?? null,
            'name' => is_string($d['name'] ?? null) ? htmlspecialchars($d['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['name'] ?? null),
            'slug' => $d['slug'] ?? null,
            'description' => is_string($d['description'] ?? null) ? htmlspecialchars($d['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['description'] ?? null),
            'color' => is_string($d['color'] ?? null) ? htmlspecialchars($d['color'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['color'] ?? null),
            'icon' => $d['icon'] ?? null,
            'parent_id' => isset($d['parent_id']) && is_numeric($d['parent_id']) ? (int) $d['parent_id'] : null,
            'sort_order' => isset($d['sort_order']) && is_numeric($d['sort_order']) ? (int) $d['sort_order'] : 0,
            'is_active' => isset($d['is_active']) ? (bool) $d['is_active'] : true,
            'created_at' => $d['created_at'] ?? null,
            'updated_at' => $d['updated_at'] ?? null,
        ];
    }
}
