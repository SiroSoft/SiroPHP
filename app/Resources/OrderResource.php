<?php

declare(strict_types=1);

namespace App\Resources;

use Siro\Core\Resource;

final class OrderResource extends Resource
{
    public function toArray(): array
    {
        $d = $this->data;
        $items = $d['items'] ?? null;
        if (is_string($items)) {
            $items = json_decode($items, true) ?? [];
        }
        if (!is_array($items)) {
            $items = [];
        }

        $id = $d['id'] ?? 0;
        $customerName = $d['customer_name'] ?? '';
        $customerEmail = $d['customer_email'] ?? '';
        $total = $d['total'] ?? 0;
        $status = $d['status'] ?? 'pending';
        $createdAt = $d['created_at'] ?? '';
        $updatedAt = $d['updated_at'] ?? '';
        /** @var int|string $id */
        /** @var string $customerName */
        /** @var string $customerEmail */
        /** @var float|int|string $total */
        /** @var string $status */
        /** @var string $createdAt */
        /** @var string $updatedAt */

        return [
            'id' => (int) $id,
            'customer_name' => htmlspecialchars($customerName, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'customer_email' => htmlspecialchars($customerEmail, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'total' => (float) $total,
            'status' => is_string($status) ? htmlspecialchars($status, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $status,
            'items' => $items,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }
}
