<?php

namespace App\Actions;

use App\Models\Order;
use App\Services\InventoryService;
use App\Events\OrderCancelled;
use Illuminate\Support\Facades\DB;

class CancelOrderAction
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function execute(Order $order, string $reason = null): void
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception("Order cannot be cancelled in current status");
        }

        DB::transaction(function () use ($order, $reason) {
            // Restore inventory if order was confirmed
            if ($order->status === 'processing') {
                foreach ($order->items as $item) {
                    $inventoriable = $item->getInventoriable();
                    $this->inventoryService->restoreStock(
                        $inventoriable,
                        $item->quantity,
                        "Order #{$order->order_number} cancelled"
                    );
                }
            }

            $order->update(['cancellation_reason' => $reason]);
            $order->updateStatus('cancelled', null, $reason);

            event(new OrderCancelled($order));
        });
    }
}
