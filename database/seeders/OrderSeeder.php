<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Customer 1 (John Smith - ID: 5) - Completed order
        $this->createOrder(5, 'delivered', [
            ['product_id' => 2, 'quantity' => 1, 'price' => 899.99],
            ['product_id' => 3, 'variant_id' => 6, 'quantity' => 1, 'price' => 199.99],
        ], '2024-11-10 10:30:00');

        // Customer 2 (Sarah Johnson - ID: 6) - Processing order
        $this->createOrder(6, 'processing', [
            ['product_id' => 1, 'variant_id' => 1, 'quantity' => 1, 'price' => 1299.99],
        ], '2024-11-18 14:20:00');

        // Customer 3 (Michael Brown - ID: 7) - Shipped order
        $this->createOrder(7, 'shipped', [
            ['product_id' => 4, 'variant_id' => 7, 'quantity' => 2, 'price' => 999.99],
            ['product_id' => 5, 'quantity' => 1, 'price' => 89.99],
        ], '2024-11-15 09:15:00');

        // Customer 4 (Emily Davis - ID: 8) - Pending order
        $this->createOrder(8, 'pending', [
            ['product_id' => 6, 'variant_id' => 12, 'quantity' => 1, 'price' => 249.99],
            ['product_id' => 7, 'quantity' => 1, 'price' => 129.99],
        ], '2024-11-19 16:45:00');

        // Customer 5 (David Wilson - ID: 9) - Multiple orders
        $this->createOrder(9, 'delivered', [
            ['product_id' => 4, 'variant_id' => 25, 'quantity' => 3, 'price' => 29.99],
            ['product_id' => 4, 'variant_id' => 30, 'quantity' => 2, 'price' => 29.99],
        ], '2024-11-05 11:00:00');

        $this->createOrder(9, 'processing', [
            ['product_id' => 5, 'variant_id' => 45, 'quantity' => 1, 'price' => 79.99],
        ], '2024-11-17 13:30:00');

        // Customer 1 (John Smith) - Cancelled order
        $this->createOrder(5, 'cancelled', [
            ['product_id' => 1, 'variant_id' => 2, 'quantity' => 1, 'price' => 1599.99],
        ], '2024-11-12 15:00:00', 'Customer changed mind');

        // Customer 2 (Sarah Johnson) - Another pending order
        $this->createOrder(6, 'pending', [
            ['product_id' => 3, 'quantity' => 2, 'price' => 199.99],
            ['product_id' => 7, 'quantity' => 1, 'price' => 129.99],
        ], '2024-11-20 08:00:00');
    }

    private function createOrder(int $customerId, string $status, array $items, string $createdAt, string $cancellationReason = null): void
    {
        $subtotal = 0;
        $tax = 0;
        $shippingFee = 15.00;
        $discount = 0;

        // Calculate subtotal
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        // Calculate tax (8%)
        $tax = round($subtotal * 0.08, 2);
        $total = $subtotal + $tax + $shippingFee - $discount;

        // Create order
        $order = Order::create([
            'order_number' => 'ORD-' . date('Ymd', strtotime($createdAt)) . '-' . strtoupper(substr(md5(uniqid()), 0, 6)),
            'customer_id' => $customerId,
            'status' => $status,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_fee' => $shippingFee,
            'discount' => $discount,
            'total' => $total,
            'currency' => 'USD',
            'shipping_address' => $this->getCustomerAddress($customerId),
            'billing_address' => $this->getCustomerAddress($customerId),
            'customer_email' => $this->getCustomerEmail($customerId),
            'customer_phone' => $this->getCustomerPhone($customerId),
            'notes' => $status === 'pending' ? 'Please deliver during business hours' : null,
            'confirmed_at' => in_array($status, ['processing', 'shipped', 'delivered', 'cancelled']) ? date('Y-m-d H:i:s', strtotime($createdAt . ' +1 hour')) : null,
            'shipped_at' => in_array($status, ['shipped', 'delivered']) ? date('Y-m-d H:i:s', strtotime($createdAt . ' +2 days')) : null,
            'delivered_at' => $status === 'delivered' ? date('Y-m-d H:i:s', strtotime($createdAt . ' +5 days')) : null,
            'cancelled_at' => $status === 'cancelled' ? date('Y-m-d H:i:s', strtotime($createdAt . ' +30 minutes')) : null,
            'cancellation_reason' => $cancellationReason,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Create order items
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $variant = isset($item['variant_id']) ? ProductVariant::find($item['variant_id']) : null;

            $itemSubtotal = $item['price'] * $item['quantity'];
            $itemTax = round($itemSubtotal * 0.08, 2);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['variant_id'] ?? null,
                'product_name' => $product->name,
                'product_sku' => $variant ? $variant->sku : $product->sku,
                'variant_attributes' => $variant ? $variant->attributes : null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'subtotal' => $itemSubtotal,
                'tax' => $itemTax,
                'total' => $itemSubtotal + $itemTax,
            ]);
        }

        // Create status history
        $order->statusHistories()->create([
            'from_status' => null,
            'to_status' => 'pending',
            'changed_by' => null,
            'notes' => 'Order created',
        ]);

        if (in_array($status, ['processing', 'shipped', 'delivered', 'cancelled'])) {
            $order->statusHistories()->create([
                'from_status' => 'pending',
                'to_status' => $status === 'cancelled' ? 'cancelled' : 'processing',
                'changed_by' => 1, // Admin
                'notes' => $status === 'cancelled' ? $cancellationReason : 'Order confirmed',
            ]);
        }

        if (in_array($status, ['shipped', 'delivered'])) {
            $order->statusHistories()->create([
                'from_status' => 'processing',
                'to_status' => 'shipped',
                'changed_by' => 1,
                'notes' => 'Order shipped via FedEx',
            ]);
        }

        if ($status === 'delivered') {
            $order->statusHistories()->create([
                'from_status' => 'shipped',
                'to_status' => 'delivered',
                'changed_by' => 1,
                'notes' => 'Order delivered successfully',
            ]);
        }
    }

    private function getCustomerAddress(int $customerId): string
    {
        $addresses = [
            5 => '111 Customer Lane, Boston, MA 02101',
            6 => '222 Buyer Street, Seattle, WA 98101',
            7 => '333 Shopper Ave, Miami, FL 33101',
            8 => '444 Consumer Blvd, Denver, CO 80201',
            9 => '555 Client Street, Austin, TX 78701',
        ];

        return $addresses[$customerId] ?? 'Unknown Address';
    }

    private function getCustomerEmail(int $customerId): string
    {
        $emails = [
            5 => 'john@example.com',
            6 => 'sarah@example.com',
            7 => 'michael@example.com',
            8 => 'emily@example.com',
            9 => 'david@example.com',
        ];

        return $emails[$customerId] ?? 'unknown@example.com';
    }

    private function getCustomerPhone(int $customerId): string
    {
        $phones = [
            5 => '+1234567894',
            6 => '+1234567895',
            7 => '+1234567896',
            8 => '+1234567897',
            9 => '+1234567898',
        ];

        return $phones[$customerId] ?? '+0000000000';
    }
}
