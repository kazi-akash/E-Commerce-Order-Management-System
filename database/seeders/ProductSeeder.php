<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // TechGear Store Products (Vendor ID: 2)
        $this->createLaptops();
        $this->createSmartphones();
        
        // Fashion Hub Products (Vendor ID: 3)
        $this->createClothing();
        
        // Home Essentials Products (Vendor ID: 4)
        $this->createHomeProducts();
    }

    private function createLaptops(): void
    {
        $laptop = Product::create([
            'vendor_id' => 2,
            'category_id' => 2,
            'name' => 'ProBook Elite 15',
            'slug' => 'probook-elite-15',
            'sku' => 'LAPTOP-001',
            'description' => 'High-performance laptop with Intel Core i7, perfect for professionals and gamers. Features a stunning 15.6" display, backlit keyboard, and all-day battery life.',
            'base_price' => 1299.99,
            'has_variants' => true,
            'is_active' => true,
            'low_stock_threshold' => 5,
            'images' => ['laptop1.jpg', 'laptop2.jpg', 'laptop3.jpg'],
            'meta_data' => [
                'brand' => 'ProBook',
                'warranty' => '2 years',
                'weight' => '1.8kg',
            ],
        ]);

        $variants = [
            ['name' => '16GB RAM / 512GB SSD', 'price' => 1299.99, 'stock' => 25, 'attributes' => ['ram' => '16GB', 'storage' => '512GB']],
            ['name' => '32GB RAM / 1TB SSD', 'price' => 1599.99, 'stock' => 15, 'attributes' => ['ram' => '32GB', 'storage' => '1TB']],
            ['name' => '16GB RAM / 1TB SSD', 'price' => 1449.99, 'stock' => 20, 'attributes' => ['ram' => '16GB', 'storage' => '1TB']],
        ];

        foreach ($variants as $index => $variantData) {
            $variant = ProductVariant::create([
                'product_id' => $laptop->id,
                'sku' => 'LAPTOP-001-V' . ($index + 1),
                'name' => $variantData['name'],
                'attributes' => $variantData['attributes'],
                'price' => $variantData['price'],
                'is_active' => true,
            ]);

            Inventory::create([
                'inventoriable_type' => ProductVariant::class,
                'inventoriable_id' => $variant->id,
                'available_quantity' => $variantData['stock'],
                'reserved_quantity' => 0,
            ]);
        }

        // Another laptop without variants
        $laptop2 = Product::create([
            'vendor_id' => 2,
            'category_id' => 2,
            'name' => 'UltraSlim Business Laptop',
            'slug' => 'ultraslim-business-laptop',
            'sku' => 'LAPTOP-002',
            'description' => 'Lightweight and portable laptop designed for business professionals. Features Intel Core i5, 8GB RAM, and 256GB SSD.',
            'base_price' => 899.99,
            'has_variants' => false,
            'is_active' => true,
            'low_stock_threshold' => 10,
            'images' => ['laptop4.jpg', 'laptop5.jpg'],
            'meta_data' => [
                'brand' => 'UltraSlim',
                'warranty' => '1 year',
                'weight' => '1.2kg',
            ],
        ]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $laptop2->id,
            'available_quantity' => 45,
            'reserved_quantity' => 0,
        ]);
    }

    private function createSmartphones(): void
    {
        $phone = Product::create([
            'vendor_id' => 2,
            'category_id' => 3,
            'name' => 'SmartPhone X Pro',
            'slug' => 'smartphone-x-pro',
            'sku' => 'PHONE-001',
            'description' => 'Latest flagship smartphone with 6.7" AMOLED display, 108MP camera, and 5G connectivity. Available in multiple colors and storage options.',
            'base_price' => 999.99,
            'has_variants' => true,
            'is_active' => true,
            'low_stock_threshold' => 10,
            'images' => ['phone1.jpg', 'phone2.jpg', 'phone3.jpg'],
            'meta_data' => [
                'brand' => 'SmartPhone',
                'warranty' => '1 year',
                'screen_size' => '6.7 inches',
            ],
        ]);

        $variants = [
            ['name' => '128GB / Black', 'price' => 999.99, 'stock' => 50, 'attributes' => ['storage' => '128GB', 'color' => 'Black']],
            ['name' => '256GB / Black', 'price' => 1099.99, 'stock' => 40, 'attributes' => ['storage' => '256GB', 'color' => 'Black']],
            ['name' => '128GB / Silver', 'price' => 999.99, 'stock' => 35, 'attributes' => ['storage' => '128GB', 'color' => 'Silver']],
            ['name' => '256GB / Silver', 'price' => 1099.99, 'stock' => 30, 'attributes' => ['storage' => '256GB', 'color' => 'Silver']],
            ['name' => '512GB / Gold', 'price' => 1299.99, 'stock' => 20, 'attributes' => ['storage' => '512GB', 'color' => 'Gold']],
        ];

        foreach ($variants as $index => $variantData) {
            $variant = ProductVariant::create([
                'product_id' => $phone->id,
                'sku' => 'PHONE-001-V' . ($index + 1),
                'name' => $variantData['name'],
                'attributes' => $variantData['attributes'],
                'price' => $variantData['price'],
                'is_active' => true,
            ]);

            Inventory::create([
                'inventoriable_type' => ProductVariant::class,
                'inventoriable_id' => $variant->id,
                'available_quantity' => $variantData['stock'],
                'reserved_quantity' => 0,
            ]);
        }

        // Wireless Earbuds
        $earbuds = Product::create([
            'vendor_id' => 2,
            'category_id' => 1,
            'name' => 'Wireless Earbuds Pro',
            'slug' => 'wireless-earbuds-pro',
            'sku' => 'AUDIO-001',
            'description' => 'Premium wireless earbuds with active noise cancellation, 30-hour battery life, and crystal-clear sound quality.',
            'base_price' => 199.99,
            'has_variants' => false,
            'is_active' => true,
            'low_stock_threshold' => 15,
            'images' => ['earbuds1.jpg', 'earbuds2.jpg'],
            'meta_data' => [
                'brand' => 'AudioTech',
                'warranty' => '1 year',
                'battery_life' => '30 hours',
            ],
        ]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $earbuds->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);
    }

    private function createClothing(): void
    {
        // Men's T-Shirt
        $tshirt = Product::create([
            'vendor_id' => 3,
            'category_id' => 5,
            'name' => 'Classic Cotton T-Shirt',
            'slug' => 'classic-cotton-tshirt',
            'sku' => 'TSHIRT-001',
            'description' => '100% premium cotton t-shirt, comfortable and breathable. Perfect for everyday wear.',
            'base_price' => 29.99,
            'has_variants' => true,
            'is_active' => true,
            'low_stock_threshold' => 20,
            'images' => ['tshirt1.jpg', 'tshirt2.jpg'],
            'meta_data' => [
                'brand' => 'FashionHub',
                'material' => '100% Cotton',
                'care' => 'Machine washable',
            ],
        ]);

        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['Black', 'White', 'Navy', 'Gray'];

        $variantIndex = 0;
        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $variantIndex++;
                $variant = ProductVariant::create([
                    'product_id' => $tshirt->id,
                    'sku' => 'TSHIRT-001-V' . $variantIndex,
                    'name' => "$size / $color",
                    'attributes' => ['size' => $size, 'color' => $color],
                    'price' => 29.99,
                    'is_active' => true,
                ]);

                Inventory::create([
                    'inventoriable_type' => ProductVariant::class,
                    'inventoriable_id' => $variant->id,
                    'available_quantity' => rand(30, 80),
                    'reserved_quantity' => 0,
                ]);
            }
        }

        // Women's Dress
        $dress = Product::create([
            'vendor_id' => 3,
            'category_id' => 6,
            'name' => 'Elegant Summer Dress',
            'slug' => 'elegant-summer-dress',
            'sku' => 'DRESS-001',
            'description' => 'Beautiful floral summer dress, perfect for any occasion. Lightweight and comfortable fabric.',
            'base_price' => 79.99,
            'has_variants' => true,
            'is_active' => true,
            'low_stock_threshold' => 10,
            'images' => ['dress1.jpg', 'dress2.jpg', 'dress3.jpg'],
            'meta_data' => [
                'brand' => 'FashionHub',
                'material' => 'Polyester blend',
                'care' => 'Hand wash recommended',
            ],
        ]);

        $dressSizes = ['XS', 'S', 'M', 'L', 'XL'];
        $dressColors = ['Floral Blue', 'Floral Pink', 'Solid Black'];

        $variantIndex = 0;
        foreach ($dressColors as $color) {
            foreach ($dressSizes as $size) {
                $variantIndex++;
                $variant = ProductVariant::create([
                    'product_id' => $dress->id,
                    'sku' => 'DRESS-001-V' . $variantIndex,
                    'name' => "$size / $color",
                    'attributes' => ['size' => $size, 'color' => $color],
                    'price' => 79.99,
                    'is_active' => true,
                ]);

                Inventory::create([
                    'inventoriable_type' => ProductVariant::class,
                    'inventoriable_id' => $variant->id,
                    'available_quantity' => rand(15, 40),
                    'reserved_quantity' => 0,
                ]);
            }
        }
    }

    private function createHomeProducts(): void
    {
        // Coffee Maker
        $coffeeMaker = Product::create([
            'vendor_id' => 4,
            'category_id' => 7,
            'name' => 'Premium Coffee Maker',
            'slug' => 'premium-coffee-maker',
            'sku' => 'COFFEE-001',
            'description' => 'Programmable coffee maker with 12-cup capacity, auto-brew feature, and keep-warm function. Makes perfect coffee every time.',
            'base_price' => 89.99,
            'has_variants' => false,
            'is_active' => true,
            'low_stock_threshold' => 8,
            'images' => ['coffee1.jpg', 'coffee2.jpg'],
            'meta_data' => [
                'brand' => 'HomeEssentials',
                'warranty' => '2 years',
                'capacity' => '12 cups',
            ],
        ]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $coffeeMaker->id,
            'available_quantity' => 60,
            'reserved_quantity' => 0,
        ]);

        // Office Chair
        $chair = Product::create([
            'vendor_id' => 4,
            'category_id' => 8,
            'name' => 'Ergonomic Office Chair',
            'slug' => 'ergonomic-office-chair',
            'sku' => 'CHAIR-001',
            'description' => 'Comfortable ergonomic office chair with lumbar support, adjustable height, and breathable mesh back. Perfect for long work hours.',
            'base_price' => 249.99,
            'has_variants' => true,
            'is_active' => true,
            'low_stock_threshold' => 5,
            'images' => ['chair1.jpg', 'chair2.jpg', 'chair3.jpg'],
            'meta_data' => [
                'brand' => 'HomeEssentials',
                'warranty' => '3 years',
                'weight_capacity' => '300 lbs',
            ],
        ]);

        $chairColors = ['Black', 'Gray', 'Blue'];

        $variantIndex = 0;
        foreach ($chairColors as $color) {
            $variantIndex++;
            $variant = ProductVariant::create([
                'product_id' => $chair->id,
                'sku' => 'CHAIR-001-V' . $variantIndex,
                'name' => $color,
                'attributes' => ['color' => $color],
                'price' => 249.99,
                'is_active' => true,
            ]);

            Inventory::create([
                'inventoriable_type' => ProductVariant::class,
                'inventoriable_id' => $variant->id,
                'available_quantity' => rand(10, 25),
                'reserved_quantity' => 0,
            ]);
        }

        // Blender
        $blender = Product::create([
            'vendor_id' => 4,
            'category_id' => 7,
            'name' => 'High-Speed Blender',
            'slug' => 'high-speed-blender',
            'sku' => 'BLEND-001',
            'description' => 'Powerful 1000W blender with multiple speed settings. Perfect for smoothies, soups, and more.',
            'base_price' => 129.99,
            'has_variants' => false,
            'is_active' => true,
            'low_stock_threshold' => 10,
            'images' => ['blender1.jpg', 'blender2.jpg'],
            'meta_data' => [
                'brand' => 'HomeEssentials',
                'warranty' => '2 years',
                'power' => '1000W',
            ],
        ]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $blender->id,
            'available_quantity' => 40,
            'reserved_quantity' => 0,
        ]);
    }
}
