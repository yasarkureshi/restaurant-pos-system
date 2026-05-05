<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'code', 'slug', 'email', 'phone',
        'address', 'city', 'state', 'pincode', 'logo', 'cover_image',
        'gst_number', 'fssai_license', 'currency_symbol', 'timezone',
        'operation_start_time', 'operation_end_time', 'is_24x7', 'is_active', 'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_24x7' => 'boolean',
        'settings' => 'array',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function tables()
    {
        return $this->hasMany(Table::class);
    }

    public function tableSections()
    {
        return $this->hasMany(TableSection::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function taxCategories()
    {
        return $this->hasMany(TaxCategory::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function isOpen(): bool
    {
        if ($this->is_24x7) return true;
        $now = now()->setTimezone($this->timezone ?? 'Asia/Kolkata');
        $currentTime = $now->format('H:i:s');
        return $currentTime >= $this->operation_start_time && $currentTime <= $this->operation_end_time;
    }
}
