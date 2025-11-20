<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'description' => 'Electronic devices and accessories',
            ],
            [
                'name' => 'Computers & Laptops',
                'description' => 'Desktop computers, laptops, and accessories',
            ],
            [
                'name' => 'Smartphones & Tablets',
                'description' => 'Mobile phones and tablet devices',
            ],
            [
                'name' => 'Fashion',
                'description' => 'Clothing, shoes, and accessories',
            ],
            [
                'name' => 'Men\'s Clothing',
                'description' => 'Clothing for men',
            ],
            [
                'name' => 'Women\'s Clothing',
                'description' => 'Clothing for women',
            ],
            [
                'name' => 'Home & Kitchen',
                'description' => 'Home appliances and kitchen items',
            ],
            [
                'name' => 'Furniture',
                'description' => 'Home and office furniture',
            ],
            [
                'name' => 'Sports & Outdoors',
                'description' => 'Sports equipment and outdoor gear',
            ],
            [
                'name' => 'Books',
                'description' => 'Physical and digital books',
            ],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'is_active' => true,
            ]);
        }
    }
}
