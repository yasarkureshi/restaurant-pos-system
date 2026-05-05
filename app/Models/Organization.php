<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address', 'logo',
        'gst_number', 'subscription_plan', 'subscription_ends_at', 'is_active', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'subscription_ends_at' => 'date',
    ];

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }
}
