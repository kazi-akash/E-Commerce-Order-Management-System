<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'vendor_id' => User::factory()->vendor(),
            'category_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'sku' => 'SKU-' . strtoupper(Str::random(8)),
            'description' => fake()->paragraph(),
            'base_price' => fake()->randomFloat(2, 10, 1000),
            'has_variants' => false,
            'is_active' => true,
            'low_stock_threshold' => 10,
            'images' => [],
            'meta_data' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withVariants(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_variants' => true,
        ]);
    }
}
