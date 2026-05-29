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
        $d = $this->data;
        return [
            'id' => $d['id'] ?? null,
            'name' => is_string($d['name'] ?? null) ? htmlspecialchars($d['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['name'] ?? null),
            'slug' => null,
            'color' => is_string($d['color'] ?? null) ? htmlspecialchars($d['color'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['color'] ?? null),
            'description' => is_string($d['description'] ?? null) ? htmlspecialchars($d['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['description'] ?? null),
            'is_active' => isset($d['is_active']) ? (bool) $d['is_active'] : true,
            'created_at' => $d['created_at'] ?? null,
            'updated_at' => $d['updated_at'] ?? null,
        ];
    }
}
