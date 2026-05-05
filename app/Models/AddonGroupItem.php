<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddonGroupItem extends Model
{
    public $timestamps = false;
    protected $fillable = ['addon_group_id', 'name', 'price', 'is_veg', 'is_available', 'sort_order'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_veg' => 'boolean',
        'is_available' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(AddonGroup::class, 'addon_group_id');
    }
}
