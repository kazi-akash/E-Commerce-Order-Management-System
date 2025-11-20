<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products()
    {
        $vendor = User::factory()->vendor()->create();
        Product::factory()->count(5)->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($vendor)
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'base_price', 'is_active'],
                ],
            ]);
    }

    public function test_can_filter_products_by_vendor()
    {
        $vendor1 = User::factory()->vendor()->create();
        $vendor2 = User::factory()->vendor()->create();

        Product::factory()->count(3)->create(['vendor_id' => $vendor1->id]);
        Product::factory()->count(2)->create(['vendor_id' => $vendor2->id]);

        $response = $this->actingAs($vendor1)
            ->getJson('/api/v1/products?vendor_id=' . $vendor1->id);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_vendor_can_only_see_own_products()
    {
        $vendor = User::factory()->vendor()->create();
        $otherVendor = User::factory()->vendor()->create();

        Product::factory()->count(3)->create(['vendor_id' => $vendor->id]);
        Product::factory()->count(2)->create(['vendor_id' => $otherVendor->id]);

        $response = $this->actingAs($vendor)
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_get_single_product()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($vendor)
            ->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                ],
            ]);
    }

    public function test_vendor_cannot_view_other_vendor_product()
    {
        $vendor = User::factory()->vendor()->create();
        $otherVendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

        $response = $this->actingAs($vendor)
            ->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(403);
    }

    public function test_can_create_product()
    {
        $vendor = User::factory()->vendor()->create();

        $response = $this->actingAs($vendor)
            ->postJson('/api/v1/products', [
                'name' => 'Test Product',
                'base_price' => 99.99,
                'description' => 'Test description',
                'is_active' => true,
                'initial_stock' => 50,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'base_price'],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_can_create_product_with_variants()
    {
        $vendor = User::factory()->vendor()->create();

        $response = $this->actingAs($vendor)
            ->postJson('/api/v1/products', [
                'name' => 'T-Shirt',
                'base_price' => 29.99,
                'variants' => [
                    [
                        'name' => 'Small - Red',
                        'price' => 29.99,
                        'attributes' => ['size' => 'S', 'color' => 'Red'],
                        'initial_stock' => 20,
                    ],
                    [
                        'name' => 'Large - Blue',
                        'price' => 29.99,
                        'attributes' => ['size' => 'L', 'color' => 'Blue'],
                        'initial_stock' => 15,
                    ],
                ],
            ]);

        $response->assertStatus(201);

        $product = Product::where('name', 'T-Shirt')->first();
        $this->assertCount(2, $product->variants);
    }

    public function test_product_creation_requires_name_and_price()
    {
        $vendor = User::factory()->vendor()->create();

        $response = $this->actingAs($vendor)
            ->postJson('/api/v1/products', [
                'description' => 'Missing required fields',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'base_price']);
    }

    public function test_can_update_product()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($vendor)
            ->putJson('/api/v1/products/' . $product->id, [
                'name' => 'Updated Product Name',
                'base_price' => 149.99,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Product updated successfully',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'base_price' => 149.99,
        ]);
    }

    public function test_vendor_cannot_update_other_vendor_product()
    {
        $vendor = User::factory()->vendor()->create();
        $otherVendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

        $response = $this->actingAs($vendor)
            ->putJson('/api/v1/products/' . $product->id, [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_product()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($vendor)
            ->deleteJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Product deleted successfully']);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_vendor_cannot_delete_other_vendor_product()
    {
        $vendor = User::factory()->vendor()->create();
        $otherVendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

        $response = $this->actingAs($vendor)
            ->deleteJson('/api/v1/products/' . $product->id);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_products()
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }
}
