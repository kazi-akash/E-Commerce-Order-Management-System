<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Inventory;
use App\Services\OrderService;
use App\Services\InventoryService;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();

        $orderRepository = new OrderRepository();
        $inventoryService = new InventoryService();
        $this->orderService = new OrderService($orderRepository, $inventoryService);
    }

    public function test_create_order_generates_order_number()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 50]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $data = [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $order = $this->orderService->createOrder($data, $customer->id);

        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('ORD-', $order->order_number);
    }

    public function test_create_order_calculates_subtotal_correctly()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product1 = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 50]);
        $product2 = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 30]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product1->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product2->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $data = [
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 3],
            ],
        ];

        $order = $this->orderService->createOrder($data, $customer->id);

        $this->assertEquals(190, $order->subtotal); // (50 * 2) + (30 * 3)
    }

    public function test_create_order_calculates_total_with_fees()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 100]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $data = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'tax' => 10,
            'shipping_fee' => 5,
            'discount' => 5,
        ];

        $order = $this->orderService->createOrder($data, $customer->id);

        $this->assertEquals(100, $order->subtotal);
        $this->assertEquals(110, $order->total); // 100 + 10 + 5 - 5
    }

    public function test_create_order_creates_order_items()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 50]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $data = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        $order = $this->orderService->createOrder($data, $customer->id);

        $this->assertCount(1, $order->items);
        $this->assertEquals(2, $order->items->first()->quantity);
        $this->assertEquals(50, $order->items->first()->unit_price);
    }

    public function test_create_order_sets_default_status_to_pending()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 50]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $data = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $order = $this->orderService->createOrder($data, $customer->id);

        $this->assertEquals('pending', $order->status);
    }

    public function test_create_order_stores_customer_information()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 50]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $data = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => '123 Main St',
            'billing_address' => '456 Oak Ave',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '+1234567890',
            'notes' => 'Please deliver before 5 PM',
        ];

        $order = $this->orderService->createOrder($data, $customer->id);

        $this->assertEquals('123 Main St', $order->shipping_address);
        $this->assertEquals('456 Oak Ave', $order->billing_address);
        $this->assertEquals('customer@example.com', $order->customer_email);
        $this->assertEquals('+1234567890', $order->customer_phone);
        $this->assertEquals('Please deliver before 5 PM', $order->notes);
    }

    public function test_create_order_throws_exception_for_invalid_product()
    {
        $customer = User::factory()->customer()->create();

        $data = [
            'items' => [
                ['product_id' => 99999, 'quantity' => 1],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Product not found');

        $this->orderService->createOrder($data, $customer->id);
    }

    public function test_update_order_status_creates_status_history()
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $this->orderService->updateOrderStatus($order, 'processing', $admin->id, 'Order confirmed');

        $this->assertEquals('processing', $order->fresh()->status);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'processing',
            'changed_by' => $admin->id,
            'notes' => 'Order confirmed',
        ]);
    }

    public function test_update_order_status_updates_timestamps()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $this->orderService->updateOrderStatus($order, 'processing');
        $this->assertNotNull($order->fresh()->confirmed_at);

        $this->orderService->updateOrderStatus($order, 'shipped');
        $this->assertNotNull($order->fresh()->shipped_at);

        $this->orderService->updateOrderStatus($order, 'delivered');
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_create_order_is_transactional()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 50]);

        $data = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => 99999, 'quantity' => 1], // Invalid product
            ],
        ];

        try {
            $this->orderService->createOrder($data, $customer->id);
        } catch (\Exception $e) {
            // Expected exception
        }

        // No order should be created
        $this->assertEquals(0, Order::count());
    }

    public function test_generate_order_number_includes_date()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id, 'base_price' => 50]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $data = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'shipping_address' => '123 Test St',
        ];

        $order = $this->orderService->createOrder($data, $customer->id);

        $expectedDate = date('Ymd');
        $this->assertStringContainsString($expectedDate, $order->order_number);
    }
}
