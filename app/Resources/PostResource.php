<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class PostResource extends Resource
{
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'title' => is_string($this->data['title'] ?? null) ? htmlspecialchars($this->data['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['title'] ?? null),
            'body' => is_string($this->data['body'] ?? null) ? htmlspecialchars($this->data['body'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : ($this->data['body'] ?? null),
            'locale' => $this->data['locale'] ?? null,
            'status' => $this->data['status'] ?? null,
            'image' => $this->data['image'] ?? null,
            'created_at' => $this->data['created_at'] ?? null,
            'updated_at' => $this->data['updated_at'] ?? null,
        ];
    }
}
