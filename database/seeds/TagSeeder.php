<?php

declare(strict_types=1);

use Siro\Core\DB;

final class TagSeeder
{
    public function run(): void
    {
        $tags = [
            'New Arrival',
            'Best Seller',
            'On Sale',
            'Limited Edition',
            'Eco Friendly',
            'Premium',
            'Budget Pick',
            'Staff Choice',
            'Trending Now',
            'Back In Stock',
            'Free Shipping',
            'Extended Warranty',
        ];

        $now = date('Y-m-d H:i:s');
        $inserted = 0;

        foreach ($tags as $name) {
            $existing = DB::table('tags')->where('name', $name)->first();
            if ($existing) {
                continue;
            }
            DB::table('tags')->insert([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $inserted++;
        }

        echo "  Created {$inserted} tags (" . (count($tags) - $inserted) . " skipped)\n";
    }
}
