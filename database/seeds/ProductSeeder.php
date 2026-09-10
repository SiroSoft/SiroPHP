<?php

declare(strict_types=1);

use Siro\Core\DB;

final class ProductSeeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Wireless Bluetooth Headphones',
                'description' => 'Premium noise-cancelling wireless headphones with 30-hour battery life, comfortable over-ear design, and crystal-clear audio quality.',
                'price' => 149.99,
                'stock' => 45,
                'category' => 'Electronics',
                'status' => 'active',
            ],
            [
                'name' => 'Organic Cotton T-Shirt',
                'description' => 'Soft, breathable organic cotton t-shirt. Available in multiple colors. Ethically sourced and environmentally friendly.',
                'price' => 29.99,
                'stock' => 200,
                'category' => 'Clothing',
                'status' => 'active',
            ],
            [
                'name' => 'Stainless Steel Water Bottle',
                'description' => 'Double-wall insulated water bottle. Keeps drinks cold for 24 hours or hot for 12 hours. BPA-free, 750ml capacity.',
                'price' => 24.99,
                'stock' => 150,
                'category' => 'Home & Living',
                'status' => 'active',
            ],
            [
                'name' => 'Ergonomic Office Chair',
                'description' => 'Full mesh ergonomic office chair with lumbar support, adjustable armrests, and breathable backrest. Perfect for long working hours.',
                'price' => 399.99,
                'stock' => 10,
                'category' => 'Furniture',
                'status' => 'active',
            ],
            [
                'name' => 'Smart Fitness Watch',
                'description' => 'Advanced fitness tracker with heart rate monitoring, GPS, sleep tracking, and 7-day battery life. Water resistant to 50 meters.',
                'price' => 199.99,
                'stock' => 30,
                'category' => 'Electronics',
                'status' => 'active',
            ],
            [
                'name' => 'Leather Messenger Bag',
                'description' => 'Handcrafted genuine leather messenger bag with padded laptop compartment, multiple pockets, and adjustable shoulder strap.',
                'price' => 89.99,
                'stock' => 25,
                'category' => 'Accessories',
                'status' => 'active',
            ],
            [
                'name' => 'Professional Chef Knife Set',
                'description' => 'German stainless steel knife set including chef knife, bread knife, utility knife, and paring knife with wooden block.',
                'price' => 129.99,
                'stock' => 40,
                'category' => 'Kitchen',
                'status' => 'active',
            ],
            [
                'name' => 'Yoga Mat Premium',
                'description' => 'Extra thick 6mm eco-friendly TPE yoga mat with alignment lines. Non-slip surface, lightweight and portable with carrying strap.',
                'price' => 39.99,
                'stock' => 80,
                'category' => 'Sports',
                'status' => 'active',
            ],
            [
                'name' => 'Wireless Charging Pad',
                'description' => 'Fast wireless charger compatible with all Qi-enabled devices. Slim design with LED indicator and overcharge protection.',
                'price' => 19.99,
                'stock' => 120,
                'category' => 'Electronics',
                'status' => 'active',
            ],
            [
                'name' => 'Bamboo Desk Organizer',
                'description' => 'Natural bamboo desktop organizer with multiple compartments for pens, phone, notes, and office supplies. Eco-friendly and stylish.',
                'price' => 34.99,
                'stock' => 65,
                'category' => 'Office',
                'status' => 'active',
            ],
            [
                'name' => 'Mechanical Gaming Keyboard',
                'description' => 'RGB backlit mechanical keyboard with Cherry MX Blue switches. N-key rollover, aluminum frame, and detachable USB-C cable.',
                'price' => 79.99,
                'stock' => 50,
                'category' => 'Electronics',
                'status' => 'active',
            ],
            [
                'name' => 'Scented Candle Collection',
                'description' => 'Set of 3 hand-poured soy wax candles in lavender, vanilla, and eucalyptus. Long-lasting 40-hour burn time each.',
                'price' => 29.99,
                'stock' => 90,
                'category' => 'Home & Living',
                'status' => 'inactive',
            ],
        ];

        $now = date('Y-m-d H:i:s');

        $existingCount = DB::table('products')->count();
        if ($existingCount > 0) {
            echo '  [SKIP] ' . $existingCount . " products already exist\n";
            return;
        }

        $owner = DB::table('users')->orderBy('id')->first();
        if ($owner === null) {
            echo "  [SKIP] No users found. Run UserSeeder first (ADMIN_EMAIL/ADMIN_PASSWORD).\n";
            return;
        }
        $ownerId = (int) ($owner['id'] ?? 0);

        foreach ($products as $product) {
            DB::table('products')->insert([
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'category' => $product['category'],
                'status' => $product['status'],
                'user_id' => $ownerId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        echo '  Created ' . count($products) . " products\n";
    }
}
