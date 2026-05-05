<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'restaurant_id', 'parent_id', 'name', 'slug', 'description', 'image',
        'icon', 'sort_order', 'is_active', 'is_veg', 'display_in_pos',
        'display_in_kds', 'display_in_online',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_veg' => 'boolean',
        'display_in_pos' => 'boolean',
        'display_in_kds' => 'boolean',
        'display_in_online' => 'boolean',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
