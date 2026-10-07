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
            'email_verified_at' => $d['email_verified_at'] ?? null,
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

    /**
     * Admin list/detail projection. Contact data is not needed for administration
     * and must not become an accidental bulk-export surface.
     *
     * @param array<string, mixed>|\Siro\Core\Model $item
     * @return array<string, mixed>
     */
    public static function admin(array|\Siro\Core\Model $item): array
    {
        $data = is_array($item) ? $item : $item->toArray();
        return [
            'id' => $data['id'] ?? null,
            'name' => isset($data['name']) && is_string($data['name'])
                ? htmlspecialchars($data['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            'role' => $data['role'] ?? 'user',
            'status' => match (isset($data['status']) && is_numeric($data['status']) ? (int) $data['status'] : 1) {
                0 => 'inactive', 2 => 'suspended', default => 'active'
            },
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }
}
