<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxCategory extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'restaurant_id', 'name', 'tax_percentage', 'cgst_percentage', 'sgst_percentage', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tax_percentage' => 'decimal:2',
        'cgst_percentage' => 'decimal:2',
        'sgst_percentage' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
