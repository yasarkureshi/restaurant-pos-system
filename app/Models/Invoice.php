<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'order_id', 'restaurant_id', 'invoice_number', 'invoice_type',
        'customer_name', 'customer_gstin', 'customer_address',
        'sub_total', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'cess_amount', 'discount_amount', 'round_off', 'grand_total',
        'amount_in_words', 'status', 'printed', 'print_count',
    ];

    protected $casts = [
        'sub_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'printed' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
