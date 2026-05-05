<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'restaurant_id', 'name', 'company_name', 'contact_person', 'phone',
        'email', 'address', 'city', 'state', 'pincode', 'gst_number',
        'payment_terms', 'delivery_schedule', 'is_active', 'notes',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
