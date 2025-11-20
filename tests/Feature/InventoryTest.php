<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_stock_to_product()
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/inventory/add', [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 50,
                'reason' => 'Restocking',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Stock added successfully']);

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product->id,
            'available_quantity' => 60,
        ]);
    }

    public function test_vendor_can_add_stock_to_own_product()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($vendor)
            ->postJson('/api/v1/inventory/add', [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 25,
            ]);

        $response->assertStatus(200);
    }

    public function test_customer_cannot_add_stock()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/inventory/add', [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 50,
            ]);

        $response->assertStatus(403);
    }

    public function test_can_add_stock_to_variant()
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        Inventory::create([
            'inventoriable_type' => ProductVariant::class,
            'inventoriable_id' => $variant->id,
            'available_quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/inventory/add', [
                'type' => 'variant',
                'id' => $variant->id,
                'quantity' => 20,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $variant->id,
            'inventoriable_type' => ProductVariant::class,
            'available_quantity' => 25,
        ]);
    }

    public function test_admin_can_deduct_stock()
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/inventory/deduct', [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 10,
                'reason' => 'Damaged goods',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Stock deducted successfully']);

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product->id,
            'available_quantity' => 40,
            'reserved_quantity' => 10,
        ]);
    }

    public function test_vendor_can_deduct_stock_from_own_product()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($vendor)
            ->postJson('/api/v1/inventory/deduct', [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 5,
            ]);

        $response->assertStatus(200);
    }

    public function test_cannot_deduct_more_than_available_stock()
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/inventory/deduct', [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 20,
            ]);

        $response->assertStatus(400);
    }

    public function test_inventory_operations_create_logs()
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($admin)
            ->postJson('/api/v1/inventory/add', [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 50,
                'reason' => 'Restocking from supplier',
            ]);

        $this->assertDatabaseHas('inventory_logs', [
            'type' => 'addition',
            'quantity' => 50,
            'reason' => 'Restocking from supplier',
            'performed_by' => $admin->id,
        ]);
    }

    public function test_add_stock_requires_valid_type()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/inventory/add', [
                'type' => 'invalid',
                'id' => 1,
                'quantity' => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_add_stock_requires_positive_quantity()
    {
        $admin = User::factory()->admin()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/inventory/add', [
                'type' => 'product',
                'id' => $product->id,
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_returns_404_for_non_existent_product()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/inventory/add', [
                'type' => 'product',
                'id' => 99999,
                'quantity' => 50,
            ]);

        $response->assertStatus(404);
    }
}
