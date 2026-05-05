<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'restaurant_id', 'name', 'phone', 'email', 'address', 'date_of_birth',
        'anniversary_date', 'gender', 'preferences', 'allergies', 'favorite_items',
        'total_visits', 'total_spent', 'last_visit_at', 'customer_type', 'tags', 'notes', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_of_birth' => 'date',
        'anniversary_date' => 'date',
        'last_visit_at' => 'datetime',
        'preferences' => 'array',
        'allergies' => 'array',
        'favorite_items' => 'array',
        'tags' => 'array',
        'total_spent' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function wallet()
    {
        return $this->hasOne(CustomerWallet::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
