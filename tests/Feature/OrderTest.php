<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_orders()
    {
        $customer = User::factory()->customer()->create();
        Order::factory()->count(3)->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_number', 'status', 'total'],
                ],
            ]);
    }

    public function test_customer_can_only_see_own_orders()
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();

        Order::factory()->count(2)->create(['customer_id' => $customer->id]);
        Order::factory()->count(3)->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($customer)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_can_see_all_orders()
    {
        $admin = User::factory()->admin()->create();
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();

        Order::factory()->count(2)->create(['customer_id' => $customer1->id]);
        Order::factory()->count(3)->create(['customer_id' => $customer2->id]);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_can_get_single_order()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer)
            ->getJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);
    }

    public function test_customer_cannot_view_other_customer_order()
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($customer)
            ->getJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(403);
    }

    public function test_can_create_order()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders', [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
                'shipping_address' => '123 Main St',
                'customer_email' => $customer->email,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'order_number', 'items'],
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);
    }

    public function test_order_creation_requires_items()
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders', [
                'shipping_address' => '123 Main St',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_order_calculates_totals_correctly()
    {
        $customer = User::factory()->customer()->create();
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'base_price' => 50.00,
        ]);

        Inventory::create([
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders', [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
                'tax' => 10.00,
                'shipping_fee' => 5.00,
                'discount' => 5.00,
            ]);

        $response->assertStatus(201);

        $order = Order::first();
        $this->assertEquals(100.00, $order->subtotal); // 50 * 2
        $this->assertEquals(110.00, $order->total); // 100 + 10 + 5 - 5
    }

    public function test_admin_can_confirm_order()
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/orders/' . $order->id . '/confirm');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Order confirmed successfully']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    public function test_vendor_can_confirm_order()
    {
        $vendor = User::factory()->vendor()->create();
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($vendor)
            ->postJson('/api/v1/orders/' . $order->id . '/confirm');

        $response->assertStatus(200);
    }

    public function test_customer_cannot_confirm_order()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders/' . $order->id . '/confirm');

        $response->assertStatus(403);
    }

    public function test_can_cancel_order()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders/' . $order->id . '/cancel', [
                'reason' => 'Changed my mind',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Order cancelled successfully']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Changed my mind',
        ]);
    }

    public function test_customer_cannot_cancel_other_customer_order()
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $order = Order::factory()->pending()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/orders/' . $order->id . '/cancel');

        $response->assertStatus(403);
    }

    public function test_admin_can_update_order_status()
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->processing()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($admin)
            ->patchJson('/api/v1/orders/' . $order->id . '/status', [
                'status' => 'shipped',
                'notes' => 'Shipped via FedEx',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped',
        ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'shipped',
        ]);
    }

    public function test_customer_cannot_update_order_status()
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->processing()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer)
            ->patchJson('/api/v1/orders/' . $order->id . '/status', [
                'status' => 'shipped',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_filter_orders_by_status()
    {
        $customer = User::factory()->customer()->create();
        Order::factory()->count(2)->pending()->create(['customer_id' => $customer->id]);
        Order::factory()->count(3)->processing()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer)
            ->getJson('/api/v1/orders?status=pending');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
}
