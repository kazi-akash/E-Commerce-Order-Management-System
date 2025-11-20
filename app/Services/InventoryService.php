<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryLog;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function deductStock($inventoriable, int $quantity, string $reason = null): void
    {
        DB::transaction(function () use ($inventoriable, $quantity, $reason) {
            $inventory = $inventoriable->inventory;

            if (!$inventory || $inventory->available_quantity < $quantity) {
                throw new \Exception("Insufficient stock");
            }

            $inventory->decrement('available_quantity', $quantity);
            $inventory->increment('reserved_quantity', $quantity);

            $this->logInventoryChange($inventory, 'deduction', -$quantity, $reason);
        });
    }

    public function restoreStock($inventoriable, int $quantity, string $reason = null): void
    {
        DB::transaction(function () use ($inventoriable, $quantity, $reason) {
            $inventory = $inventoriable->inventory;

            if (!$inventory) {
                return;
            }

            $inventory->increment('available_quantity', $quantity);
            $inventory->decrement('reserved_quantity', $quantity);

            $this->logInventoryChange($inventory, 'restoration', $quantity, $reason);
        });
    }

    public function addStock($inventoriable, int $quantity, string $reason = null): void
    {
        DB::transaction(function () use ($inventoriable, $quantity, $reason) {
            $inventory = $inventoriable->inventory;

            if (!$inventory) {
                $inventory = Inventory::create([
                    'inventoriable_type' => get_class($inventoriable),
                    'inventoriable_id' => $inventoriable->id,
                    'available_quantity' => $quantity,
                    'reserved_quantity' => 0,
                ]);
            } else {
                $inventory->increment('available_quantity', $quantity);
            }

            $this->logInventoryChange($inventory, 'addition', $quantity, $reason);
        });
    }

    private function logInventoryChange(Inventory $inventory, string $type, int $quantity, string $reason = null): void
    {
        InventoryLog::create([
            'inventory_id' => $inventory->id,
            'type' => $type,
            'quantity' => $quantity,
            'previous_quantity' => $inventory->available_quantity - $quantity,
            'new_quantity' => $inventory->available_quantity,
            'reason' => $reason,
            'performed_by' => auth()->id(),
        ]);
    }
}
