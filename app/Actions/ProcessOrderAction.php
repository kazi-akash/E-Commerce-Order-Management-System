<?php

namespace App\Actions;

use App\Models\Order;
use App\Services\InventoryService;
use App\Events\OrderConfirmed;
use Illuminate\Support\Facades\DB;

class ProcessOrderAction
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function execute(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Deduct inventory for each item
            foreach ($order->items as $item) {
                $inventoriable = $item->getInventoriable();
                $this->inventoryService->deductStock(
                    $inventoriable,
                    $item->quantity,
                    "Order #{$order->order_number}"
                );
            }

            // Update order status
            $order->updateStatus('processing', null, 'Order confirmed and inventory deducted');

            // Fire event for notifications
            event(new OrderConfirmed($order));
        });
    }
}
