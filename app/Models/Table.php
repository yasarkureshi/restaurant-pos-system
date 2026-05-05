<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = [
        'restaurant_id', 'section_id', 'name', 'table_number', 'capacity',
        'minimum_capacity', 'table_type', 'position_x', 'position_y',
        'width', 'height', 'shape', 'rotation', 'status', 'current_order_id',
        'is_active', 'meta_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function section()
    {
        return $this->belongsTo(TableSection::class, 'section_id');
    }

    public function currentOrder()
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reservations()
    {
        return $this->hasMany(TableReservation::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}
