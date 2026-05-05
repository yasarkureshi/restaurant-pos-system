<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TableSection extends Model
{
    public $timestamps = false;
    protected $fillable = ['restaurant_id', 'name', 'description', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class, 'section_id');
    }
}
