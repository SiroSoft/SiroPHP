<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class PostResource extends Resource
{
    public function toArray(): array
    {
        $d = $this->data;
        return [
            'id' => $d['id'] ?? null,
            'title' => is_string($d['title'] ?? null) ? htmlspecialchars($d['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['title'] ?? null),
            'content' => is_string($d['body'] ?? null) ? htmlspecialchars($d['body'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['body'] ?? null),
            'excerpt' => $d['excerpt'] ?? null,
            'locale' => $d['locale'] ?? null,
            'status' => $d['status'] ?? null,
            'featured' => isset($d['featured']) ? (bool) $d['featured'] : false,
            'cover_image' => is_string($d['image'] ?? null) ? htmlspecialchars($d['image'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($d['image'] ?? null),
            'created_at' => $d['created_at'] ?? null,
            'updated_at' => $d['updated_at'] ?? null,
        ];
    }
}
