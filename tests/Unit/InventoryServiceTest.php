<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    public function test_add_stock_creates_inventory_if_not_exists()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $this->inventoryService->addStock($product, 50, 'Initial stock');

        $this->assertDatabaseHas('inventories', [
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_add_stock_increments_existing_inventory()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $this->inventoryService->addStock($product, 25, 'Restocking');

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product->id,
            'available_quantity' => 35,
        ]);
    }

    public function test_add_stock_creates_inventory_log()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $this->inventoryService->addStock($product, 50, 'Restocking from supplier');

        $this->assertDatabaseHas('inventory_logs', [
            'type' => 'addition',
            'quantity' => 50,
            'reason' => 'Restocking from supplier',
            'performed_by' => $admin->id,
        ]);
    }

    public function test_deduct_stock_decreases_available_and_increases_reserved()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $this->inventoryService->deductStock($product, 10, 'Order #123');

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product->id,
            'available_quantity' => 40,
            'reserved_quantity' => 10,
        ]);
    }

    public function test_deduct_stock_throws_exception_when_insufficient()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->inventoryService->deductStock($product, 10);
    }

    public function test_deduct_stock_throws_exception_when_no_inventory()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->inventoryService->deductStock($product, 10);
    }

    public function test_deduct_stock_creates_inventory_log()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $this->inventoryService->deductStock($product, 10, 'Damaged goods');

        $this->assertDatabaseHas('inventory_logs', [
            'type' => 'deduction',
            'quantity' => -10,
            'reason' => 'Damaged goods',
            'performed_by' => $admin->id,
        ]);
    }

    public function test_restore_stock_increases_available_and_decreases_reserved()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 40,
            'reserved_quantity' => 10,
        ]);

        $this->inventoryService->restoreStock($product, 10, 'Order cancelled');

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_restore_stock_creates_inventory_log()
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 40,
            'reserved_quantity' => 10,
        ]);

        $this->inventoryService->restoreStock($product, 10, 'Order cancelled');

        $this->assertDatabaseHas('inventory_logs', [
            'type' => 'restoration',
            'quantity' => 10,
            'reason' => 'Order cancelled',
            'performed_by' => $admin->id,
        ]);
    }

    public function test_restore_stock_handles_missing_inventory_gracefully()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        // Should not throw exception
        $this->inventoryService->restoreStock($product, 10);

        $this->assertTrue(true);
    }

    public function test_works_with_product_variants()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->inventoryService->addStock($variant, 30, 'Initial variant stock');

        $this->assertDatabaseHas('inventories', [
            'inventoriable_type' => ProductVariant::class,
            'inventoriable_id' => $variant->id,
            'available_quantity' => 30,
        ]);
    }

    public function test_inventory_operations_are_transactional()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $inventory = Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 5,
            'reserved_quantity' => 0,
        ]);

        try {
            $this->inventoryService->deductStock($product, 10);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            // Exception expected - verify it's the right exception
            $this->assertEquals('Insufficient stock', $e->getMessage());
        }

        // Refresh inventory from database to get current state
        $inventory->refresh();

        // Inventory should remain unchanged due to transaction rollback
        $this->assertEquals(5, $inventory->available_quantity);
        $this->assertEquals(0, $inventory->reserved_quantity);
    }
}
