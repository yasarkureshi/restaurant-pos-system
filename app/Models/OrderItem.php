<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'product_price', 'quantity',
        'unit_type', 'variant_id', 'variant_name', 'variant_price', 'addons_total',
        'unit_price', 'tax_percentage', 'tax_amount', 'discount_amount', 'item_total',
        'item_status', 'kot_printed', 'special_instructions', 'cancel_reason',
        'prepared_by', 'served_by', 'estimated_preparation_time', 'actual_preparation_time',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'item_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'kot_printed' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function addons()
    {
        return $this->hasMany(OrderItemAddon::class);
    }
}
