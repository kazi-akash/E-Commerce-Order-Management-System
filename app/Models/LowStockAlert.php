<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LowStockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'alertable_type',
        'alertable_id',
        'current_quantity',
        'threshold',
        'status',
        'notified_at',
        'resolved_at',
    ];

    protected $casts = [
        'current_quantity' => 'integer',
        'threshold' => 'integer',
        'notified_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function alertable()
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNotified($query)
    {
        return $query->where('status', 'notified');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    // Helper methods
    public function markAsNotified(): void
    {
        $this->update([
            'status' => 'notified',
            'notified_at' => now(),
        ]);
    }

    public function markAsResolved(): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }
}
