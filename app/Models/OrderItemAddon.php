<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemAddon extends Model
{
    public $timestamps = false;
    protected $fillable = ['order_item_id', 'addon_id', 'addon_name', 'addon_price', 'quantity'];

    protected $casts = ['addon_price' => 'decimal:2'];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
