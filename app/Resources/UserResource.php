<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

/**
 * User API resource transformer.
 *
 * Maps User model data to a structured API response,
 * exposing id, name, email, and created_at fields.
 *
 * @package App\Resources
 */
final class UserResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->data['id'] ?? null,
            'name' => $this->data['name'] ?? null,
            'email' => $this->data['email'] ?? null,
            'created_at' => $this->data['created_at'] ?? null,
        ];
    }
}
