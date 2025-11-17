<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventoriable_type',
        'inventoriable_id',
        'quantity',
        'reserved_quantity',
        'last_restocked_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'last_restocked_at' => 'datetime',
    ];

    protected $appends = ['available_quantity'];

    // Relationships
    public function inventoriable()
    {
        return $this->morphTo();
    }

    public function logs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    // Accessors
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->reserved_quantity;
    }

    // Helper methods
    public function reserve(int $quantity, string $referenceType = null, int $referenceId = null): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->increment('reserved_quantity', $quantity);

        $this->logs()->create([
            'type' => 'reservation',
            'quantity_change' => -$quantity,
            'quantity_before' => $this->quantity,
            'quantity_after' => $this->quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        return true;
    }

    public function release(int $quantity, string $referenceType = null, int $referenceId = null): void
    {
        $this->decrement('reserved_quantity', $quantity);

        $this->logs()->create([
            'type' => 'release',
            'quantity_change' => $quantity,
            'quantity_before' => $this->quantity,
            'quantity_after' => $this->quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function deduct(int $quantity, string $referenceType = null, int $referenceId = null): void
    {
        $quantityBefore = $this->quantity;
        $this->decrement('quantity', $quantity);
        $this->decrement('reserved_quantity', $quantity);

        $this->logs()->create([
            'type' => 'deduction',
            'quantity_change' => -$quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }

    public function restock(int $quantity, int $userId = null): void
    {
        $quantityBefore = $this->quantity;
        $this->increment('quantity', $quantity);
        $this->update(['last_restocked_at' => now()]);

        $this->logs()->create([
            'type' => 'restock',
            'quantity_change' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'user_id' => $userId,
        ]);
    }
}
