<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'attributes',
        'price',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->morphOne(Inventory::class, 'inventoriable');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function lowStockAlerts()
    {
        return $this->morphMany(LowStockAlert::class, 'alertable');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function getFullNameAttribute(): string
    {
        return $this->product->name . ' - ' . $this->name;
    }

    public function isLowStock(): bool
    {
        if (!$this->inventory) {
            return true;
        }
        return $this->inventory->available_quantity <= $this->product->low_stock_threshold;
    }
}
