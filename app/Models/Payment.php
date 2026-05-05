<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'restaurant_id', 'payment_number', 'amount', 'payment_method',
        'card_type', 'card_last_four', 'card_bank', 'upi_id', 'upi_transaction_id',
        'transaction_id', 'payment_gateway', 'gateway_response', 'status',
        'refund_amount', 'refund_reason', 'cash_tendered', 'cash_change', 'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'cash_tendered' => 'decimal:2',
        'cash_change' => 'decimal:2',
        'gateway_response' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
