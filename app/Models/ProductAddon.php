<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    protected $fillable = ['product_id', 'name', 'price', 'is_veg', 'is_available', 'sort_order'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_veg' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
