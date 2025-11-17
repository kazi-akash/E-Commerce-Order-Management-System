<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'base_price',
        'has_variants',
        'is_active',
        'low_stock_threshold',
        'images',
        'meta_data',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'has_variants' => 'boolean',
        'is_active' => 'boolean',
        'low_stock_threshold' => 'integer',
        'images' => 'array',
        'meta_data' => 'array',
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
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

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->whereRaw('available_quantity <= products.low_stock_threshold');
        });
    }

    // Scout searchable
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
        ];
    }

    // Helper methods
    public function isLowStock(): bool
    {
        if (!$this->inventory) {
            return true;
        }
        return $this->inventory->available_quantity <= $this->low_stock_threshold;
    }
}
