<?php

namespace Tests\Unit;

use App\Actions\ProcessOrderAction;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Services\InventoryService;
use App\Events\OrderConfirmed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProcessOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private ProcessOrderAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $inventoryService = new InventoryService();
        $this->action = new ProcessOrderAction($inventoryService);
    }

    public function test_execute_deducts_inventory_for_order_items()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
        ]);

        $inventory = Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total' => 500,
        ]);

        // Refresh order to load items relationship
        $order = $order->fresh(['items']);

        $this->action->execute($order);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'available_quantity' => 45,
            'reserved_quantity' => 5,
        ]);
    }

    public function test_execute_updates_order_status_to_processing()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total' => 500,
        ]);

        $order = $order->fresh(['items']);
        $this->action->execute($order);

        $this->assertEquals('processing', $order->fresh()->status);
    }

    public function test_execute_creates_status_history()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total' => 500,
        ]);

        $order = $order->fresh(['items']);
        $this->action->execute($order);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'processing',
        ]);
    }

    public function test_execute_fires_order_confirmed_event()
    {
        Event::fake();

        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total' => 500,
        ]);

        $order = $order->fresh(['items']);
        $this->action->execute($order);

        Event::assertDispatched(OrderConfirmed::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    public function test_execute_handles_multiple_order_items()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product1 = Product::factory()->create(['vendor_id' => $vendor->id]);
        $product2 = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product1->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product2->id,
            'available_quantity' => 30,
            'reserved_quantity' => 0,
        ]);

        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'product_name' => $product1->name,
            'product_sku' => $product1->sku,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total' => 500,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'product_sku' => $product2->sku,
            'quantity' => 3,
            'unit_price' => 50,
            'subtotal' => 150,
            'total' => 150,
        ]);

        $order = $order->fresh(['items']);
        $this->action->execute($order);

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product1->id,
            'available_quantity' => 45,
            'reserved_quantity' => 5,
        ]);

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product2->id,
            'available_quantity' => 27,
            'reserved_quantity' => 3,
        ]);
    }

    public function test_execute_is_transactional()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product1 = Product::factory()->create(['vendor_id' => $vendor->id]);
        $product2 = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product1->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        // Product 2 has insufficient stock
        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product2->id,
            'available_quantity' => 2,
            'reserved_quantity' => 0,
        ]);

        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'product_name' => $product1->name,
            'product_sku' => $product1->sku,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total' => 500,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'product_sku' => $product2->sku,
            'quantity' => 10,
            'unit_price' => 50,
            'subtotal' => 500,
            'total' => 500,
        ]);

        try {
            $this->action->execute($order);
        } catch (\Exception $e) {
            // Expected exception
        }

        // First product inventory should not be deducted
        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product1->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        // Order status should remain pending
        $this->assertEquals('pending', $order->fresh()->status);
    }

    public function test_execute_creates_inventory_logs()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $order = Order::factory()->pending()->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-20241120-ABC123',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total' => 500,
        ]);

        $order = $order->fresh(['items']);
        $this->action->execute($order);

        $this->assertDatabaseHas('inventory_logs', [
            'type' => 'deduction',
            'quantity' => -5,
            'reason' => 'Order #ORD-20241120-ABC123',
        ]);
    }
}


