<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddonGroup extends Model
{
    protected $fillable = [
        'product_id', 'name', 'description', 'min_selection', 'max_selection', 'is_required', 'sort_order',
    ];

    protected $casts = ['is_required' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->hasMany(AddonGroupItem::class);
    }
}
