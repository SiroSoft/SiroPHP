<?php

declare(strict_types=1);

use Siro\Core\DB;

final class OrderSeeder
{
    public function run(): void
    {
        $existingCount = DB::table('orders')->count();
        if ($existingCount > 0) {
            echo "  [SKIP] {$existingCount} orders already exist\n";
            return;
        }

        $owner = DB::table('users')->orderBy('id')->first();
        if ($owner === null) {
            echo "  [SKIP] No users found. Run UserSeeder first.\n";
            return;
        }
        $ownerId = (int) ($owner['id'] ?? 0);

        $products = DB::table('products')->orderBy('id')->limit(6)->get();
        if ($products === []) {
            echo "  [SKIP] No products found. Run ProductSeeder first.\n";
            return;
        }

        $customers = [
            ['Sophia Bennett', 'sophia.bennett@example.com'],
            ['Liam Carter', 'liam.carter@example.com'],
            ['Olivia Nguyen', 'olivia.nguyen@example.com'],
            ['Noah Patel', 'noah.patel@example.com'],
            ['Emma Wilson', 'emma.wilson@example.com'],
            ['Lucas Garcia', 'lucas.garcia@example.com'],
            ['Ava Thompson', 'ava.thompson@example.com'],
            ['Mason Lee', 'mason.lee@example.com'],
        ];
        $statuses = ['pending', 'processing', 'completed', 'completed', 'cancelled'];

        $now = date('Y-m-d H:i:s');
        $inserted = 0;

        foreach ($customers as $i => [$name, $email]) {
            $line = $products[$i % count($products)];
            $qty = ($i % 3) + 1;
            $price = (float) ($line['price'] ?? 0);
            $items = [[
                'product_id' => (int) ($line['id'] ?? 0),
                'name' => (string) ($line['name'] ?? ''),
                'quantity' => $qty,
                'price' => $price,
            ]];
            DB::table('orders')->insert([
                'user_id' => $ownerId,
                'customer_name' => $name,
                'customer_email' => $email,
                'total' => round($price * $qty, 2),
                'status' => $statuses[$i % count($statuses)],
                'items' => json_encode($items),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        echo "  Created {$inserted} orders\n";
    }
}
