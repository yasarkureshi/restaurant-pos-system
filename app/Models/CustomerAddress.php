<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'customer_id', 'address_type', 'address_line1', 'address_line2',
        'landmark', 'city', 'state', 'pincode', 'latitude', 'longitude', 'is_default',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
