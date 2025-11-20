<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private InventoryService $inventoryService
    ) {}

    public function createOrder(array $data, int $customerId): Order
    {
        return DB::transaction(function () use ($data, $customerId) {
            // Create order
            $order = $this->orderRepository->create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customerId,
                'status' => 'pending',
                'subtotal' => 0,
                'tax' => $data['tax'] ?? 0,
                'shipping_fee' => $data['shipping_fee'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'total' => 0,
                'currency' => $data['currency'] ?? 'USD',
                'shipping_address' => $data['shipping_address'] ?? null,
                'billing_address' => $data['billing_address'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;

            // Create order items
            foreach ($data['items'] as $item) {
                $inventoriable = $this->getInventoriable($item);
                
                if (!$inventoriable) {
                    throw new \Exception("Product not found");
                }

                $unitPrice = $inventoriable->base_price ?? $inventoriable->price;
                $itemSubtotal = $unitPrice * $item['quantity'];
                $subtotal += $itemSubtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'product_name' => $inventoriable->name,
                    'product_sku' => $inventoriable->sku,
                    'variant_attributes' => $item['variant_attributes'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'tax' => 0,
                    'total' => $itemSubtotal,
                ]);
            }

            // Update order totals
            $total = $subtotal + $order->tax + $order->shipping_fee - $order->discount;
            $order->update([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);

            return $order->fresh(['items']);
        });
    }

    public function confirmOrder(Order $order): void
    {
        app(\App\Actions\ProcessOrderAction::class)->execute($order);
    }

    public function cancelOrder(Order $order, string $reason = null): void
    {
        app(\App\Actions\CancelOrderAction::class)->execute($order, $reason);
    }

    public function updateOrderStatus(Order $order, string $status, int $userId = null, string $notes = null): void
    {
        $order->updateStatus($status, $userId, $notes);
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function getInventoriable(array $item)
    {
        if (isset($item['product_variant_id'])) {
            return ProductVariant::find($item['product_variant_id']);
        }
        return Product::find($item['product_id']);
    }
}
