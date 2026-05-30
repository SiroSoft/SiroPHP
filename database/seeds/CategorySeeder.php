<?php

declare(strict_types=1);

use Siro\Core\DB;

final class CategorySeeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'is_active' => 1],
            ['name' => 'Clothing', 'is_active' => 1],
            ['name' => 'Home & Living', 'is_active' => 1],
            ['name' => 'Furniture', 'is_active' => 1],
            ['name' => 'Accessories', 'is_active' => 1],
            ['name' => 'Kitchen', 'is_active' => 1],
            ['name' => 'Sports', 'is_active' => 1],
            ['name' => 'Office', 'is_active' => 1],
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($categories as $cat) {
            DB::table('categories')->insert([
                'name' => $cat['name'],
                'is_active' => $cat['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        echo '  Created ' . count($categories) . " categories\n";
    }
}
