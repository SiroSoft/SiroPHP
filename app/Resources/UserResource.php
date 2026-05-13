<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

/**
 * User API resource transformer.
 *
 * Excludes sensitive fields like password from API responses.
 */
final class UserResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'name' => isset($this->data['name']) ? htmlspecialchars((string) $this->data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            'email' => $this->data['email'] ?? null,
            'created_at' => $this->data['created_at'] ?? null,
        ];
    }
}
