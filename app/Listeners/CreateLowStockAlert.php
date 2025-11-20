<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Models\LowStockAlert;

class CreateLowStockAlert
{
    public function handle(LowStockDetected $event): void
    {
        LowStockAlert::create([
            'alertable_type' => get_class($event->inventoriable),
            'alertable_id' => $event->inventoriable->id,
            'current_quantity' => $event->currentStock,
            'threshold' => $event->threshold,
            'is_resolved' => false,
        ]);
    }
}
