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

        $id = $d['id'] ?? null;
        $customerName = $d['customer_name'] ?? null;
        $customerEmail = $d['customer_email'] ?? null;
        $total = $d['total'] ?? null;
        $status = $d['status'] ?? null;

        return [
            'id' => $id !== null ? (int) $id : null,
            'user_name' => is_string($customerName) ? htmlspecialchars($customerName, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $customerName,
            'customer_email' => is_string($customerEmail) ? htmlspecialchars($customerEmail, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $customerEmail,
            'total' => $total !== null ? (float) $total : null,
            'status' => is_string($status) ? htmlspecialchars($status, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $status,
            'items' => $items,
            'payment_status' => is_string($status) ? htmlspecialchars($status, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $status,
            'created_at' => $d['created_at'] ?? null,
            'updated_at' => $d['updated_at'] ?? null,
        ];
    }
}
