<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerWallet extends Model
{
    protected $fillable = [
        'customer_id', 'restaurant_id', 'balance', 'loyalty_points', 'membership_type', 'membership_expiry',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'membership_expiry' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
