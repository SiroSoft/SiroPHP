<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class UserResource extends Resource
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $d = $this->data;
        $name = $d['name'] ?? null;
        $email = $d['email'] ?? null;
        return [
            'id' => $d['id'] ?? null,
            'name' => is_string($name) ? htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            'email' => is_string($email) ? htmlspecialchars($email, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            'avatar' => $d['avatar'] ?? null,
            'phone' => $d['phone'] ?? null,
            'role' => $d['role'] ?? 'user',
            'status' => match (isset($d['status']) && is_numeric($d['status']) ? (int) $d['status'] : 1) {
            0 => 'inactive', 2 => 'suspended', default => 'active'
            },
            'created_at' => $d['created_at'] ?? null,
            'updated_at' => $d['updated_at'] ?? null,
        ];
    }
}
