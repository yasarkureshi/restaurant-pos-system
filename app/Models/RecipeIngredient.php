<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'product_id', 'inventory_item_id', 'quantity_required', 'unit_type',
        'is_optional', 'wastage_percentage', 'notes',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
        'quantity_required' => 'decimal:3',
        'wastage_percentage' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
