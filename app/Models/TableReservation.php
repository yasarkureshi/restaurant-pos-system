<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableReservation extends Model
{
    protected $fillable = [
        'restaurant_id', 'table_id', 'customer_id', 'customer_name',
        'customer_phone', 'customer_email', 'number_of_guests', 'reservation_date',
        'reservation_time', 'duration_minutes', 'special_requests', 'occasion',
        'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
