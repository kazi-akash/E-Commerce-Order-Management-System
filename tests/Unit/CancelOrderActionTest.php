<?php

namespace Tests\Unit;

use App\Actions\CancelOrderAction;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Services\InventoryService;
use App\Events\OrderCancelled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CancelOrderActionTest extends TestCase
{
    use RefreshDatabase;

    private CancelOrderAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $inventoryService = new InventoryService();
        $this->action = new CancelOrderAction($inventoryService);
    }

    public function test_execute_restores_inventory_for_processing_order()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $inventory = Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 45,
            'reserved_quantity' => 5,
        ]);

        $order = Order::factory()->processing()->create(['customer_id' => $customer->id]);

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
        $this->action->execute($order, 'Customer requested');

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_execute_does_not_restore_inventory_for_pending_order()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

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
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 5,
            'unit_price' => 100,
            'subtotal' => 500,
            'total' => 500,
        ]);

        $order = $order->fresh(['items']);
        $this->action->execute($order);

        // Inventory should remain unchanged
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_execute_updates_order_status_to_cancelled()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $this->action->execute($order);

        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    public function test_execute_stores_cancellation_reason()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $this->action->execute($order, 'Customer changed mind');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'cancellation_reason' => 'Customer changed mind',
        ]);
    }

    public function test_execute_creates_status_history()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $this->action->execute($order, 'Out of stock');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'cancelled',
            'notes' => 'Out of stock',
        ]);
    }

    public function test_execute_fires_order_cancelled_event()
    {
        Event::fake();

        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $this->action->execute($order);

        Event::assertDispatched(OrderCancelled::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    public function test_execute_throws_exception_for_shipped_order()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'shipped',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order cannot be cancelled in current status');

        $this->action->execute($order);
    }

    public function test_execute_throws_exception_for_delivered_order()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'delivered',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order cannot be cancelled in current status');

        $this->action->execute($order);
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
            'available_quantity' => 45,
            'reserved_quantity' => 5,
        ]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product2->id,
            'available_quantity' => 27,
            'reserved_quantity' => 3,
        ]);

        $order = Order::factory()->processing()->create(['customer_id' => $customer->id]);

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
            'available_quantity' => 50,
            'reserved_quantity' => 0,
        ]);

        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product2->id,
            'available_quantity' => 30,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_execute_is_transactional()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 45,
            'reserved_quantity' => 5,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'shipped', // Cannot be cancelled
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

        try {
            $this->action->execute($order);
        } catch (\Exception $e) {
            // Expected exception
        }

        // Inventory should not be restored
        $this->assertDatabaseHas('inventories', [
            'inventoriable_id' => $product->id,
            'available_quantity' => 45,
            'reserved_quantity' => 5,
        ]);

        // Order status should remain shipped
        $this->assertEquals('shipped', $order->fresh()->status);
    }

    public function test_execute_creates_inventory_logs()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 45,
            'reserved_quantity' => 5,
        ]);

        $order = Order::factory()->processing()->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-20241120-XYZ789',
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
            'type' => 'restoration',
            'quantity' => 5,
            'reason' => 'Order #ORD-20241120-XYZ789 cancelled',
        ]);
    }
}


