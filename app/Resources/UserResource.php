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
        /** @var string|null $name */
        /** @var string|null $email */
        return [
            'id' => $d['id'] ?? null,
            'name' => $name !== null ? htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            'email' => $email !== null ? htmlspecialchars($email, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null,
            'created_at' => $d['created_at'] ?? null,
        ];
    }
}
