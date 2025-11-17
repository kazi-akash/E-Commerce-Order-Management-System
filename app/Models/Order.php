<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'status',
        'subtotal',
        'tax',
        'shipping_fee',
        'discount',
        'total',
        'currency',
        'shipping_address',
        'billing_address',
        'customer_email',
        'customer_phone',
        'notes',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeShipped($query)
    {
        return $query->where('status', 'shipped');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Helper methods
    public function updateStatus(string $newStatus, int $userId = null, string $notes = null): void
    {
        $oldStatus = $this->status;
        $this->update(['status' => $newStatus]);

        $this->statusHistories()->create([
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $userId,
            'notes' => $notes,
        ]);

        // Update timestamps
        match($newStatus) {
            'processing' => $this->update(['confirmed_at' => now()]),
            'shipped' => $this->update(['shipped_at' => now()]),
            'delivered' => $this->update(['delivered_at' => now()]),
            'cancelled' => $this->update(['cancelled_at' => now()]),
            default => null,
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }
}
