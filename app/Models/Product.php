<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'restaurant_id', 'category_id', 'tax_category_id', 'name', 'slug',
        'short_description', 'description', 'image', 'additional_images',
        'price', 'cost_price', 'mrp', 'sku', 'barcode', 'hsn_code', 'unit_type',
        'is_veg', 'is_available', 'is_featured', 'is_recommended', 'is_todays_special',
        'preparation_time_minutes', 'min_quantity', 'max_quantity', 'track_inventory',
        'current_stock', 'low_stock_alert', 'discount_percentage',
        'discount_start_date', 'discount_end_date', 'kot_print_priority',
        'kot_category', 'display_order', 'tags', 'allergens', 'nutritional_info', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'is_veg' => 'boolean',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'is_recommended' => 'boolean',
        'is_todays_special' => 'boolean',
        'track_inventory' => 'boolean',
        'is_active' => 'boolean',
        'additional_images' => 'array',
        'tags' => 'array',
        'allergens' => 'array',
        'nutritional_info' => 'array',
        'discount_start_date' => 'date',
        'discount_end_date' => 'date',
    ];

    protected $appends = ['current_price', 'profit_margin'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function taxCategory()
    {
        return $this->belongsTo(TaxCategory::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function addons()
    {
        return $this->hasMany(ProductAddon::class)->orderBy('sort_order');
    }

    public function addonGroups()
    {
        return $this->hasMany(AddonGroup::class)->with('items')->orderBy('sort_order');
    }

    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    public function getCurrentPriceAttribute(): float
    {
        if ($this->discount_percentage > 0
            && $this->discount_start_date
            && now()->between($this->discount_start_date, $this->discount_end_date)) {
            return round($this->price - ($this->price * $this->discount_percentage / 100), 2);
        }
        return (float) $this->price;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price && $this->cost_price > 0) {
            return round((($this->price - $this->cost_price) / $this->price) * 100, 2);
        }
        return 0;
    }

    public function deductStock(int $quantity): bool
    {
        if (!$this->track_inventory) return true;
        if ($this->current_stock >= $quantity) {
            $this->decrement('current_stock', $quantity);
            return true;
        }
        return false;
    }
}
