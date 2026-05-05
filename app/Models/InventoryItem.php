<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'restaurant_id', 'name', 'sku', 'category', 'unit_type',
        'current_stock', 'minimum_stock', 'maximum_stock', 'reorder_point',
        'cost_per_unit', 'last_purchase_price', 'storage_location', 'expiry_date',
        'is_perishable', 'is_active', 'notes',
    ];

    protected $casts = [
        'is_perishable' => 'boolean',
        'is_active' => 'boolean',
        'current_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'expiry_date' => 'date',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_point;
    }
}
