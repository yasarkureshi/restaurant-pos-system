<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'restaurant_id', 'order_number', 'order_type', 'table_id', 'waiter_id',
        'customer_id', 'customer_name', 'customer_phone', 'number_of_guests',
        'status', 'payment_status', 'sub_total', 'tax_amount', 'discount_amount',
        'discount_type', 'discount_reason', 'service_charge_amount',
        'delivery_charge', 'packaging_charge', 'rounding_adjustment',
        'grand_total', 'paid_amount', 'balance_amount', 'special_instructions',
        'order_tags', 'order_source', 'online_order_id', 'meta_data',
        'created_by', 'cancelled_by', 'delivery_address', 'kot_number', 'kot_printed',
    ];

    protected $casts = [
        'sub_total' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'service_charge_amount' => 'decimal:2',
        'kot_printed' => 'boolean',
        'order_tags' => 'array',
        'meta_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            $order->order_number = self::generateOrderNumber($order->restaurant_id);
        });
    }

    public static function generateOrderNumber($restaurantId): string
    {
        $prefix = 'ORD';
        $dateCode = now()->format('ymd');
        $restaurant = Restaurant::find($restaurantId);
        $restaurantCode = $restaurant->code ?? 'XXX';
        $sequence = self::where('restaurant_id', $restaurantId)->whereDate('created_at', now())->count() + 1;
        return sprintf('%s-%s-%s-%04d', $prefix, $restaurantCode, $dateCode, $sequence);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function calculateTotals(): self
    {
        $this->load('items');
        $subTotal = $this->items->sum('item_total');
        $taxAmount = $this->items->sum('tax_amount');

        $discountAmount = 0;
        if ($this->discount_type === 'percentage') {
            $discountAmount = ($subTotal * $this->discount_amount) / 100;
        } elseif (in_array($this->discount_type, ['fixed', 'coupon', 'manual'])) {
            $discountAmount = $this->discount_amount;
        }

        $serviceCharge = 0;
        if ($this->order_type === 'dine_in') {
            $restaurant = $this->restaurant;
            $scRate = $restaurant->settings['service_charge_percentage'] ?? 0;
            $serviceCharge = $subTotal * ($scRate / 100);
        }

        $grandTotal = $subTotal + $taxAmount + $serviceCharge - $discountAmount
            + $this->delivery_charge + $this->packaging_charge;
        $rounding = round($grandTotal) - $grandTotal;
        $grandTotal = round($grandTotal);

        $this->sub_total = $subTotal;
        $this->tax_amount = $taxAmount;
        $this->discount_amount = $discountAmount;
        $this->service_charge_amount = $serviceCharge;
        $this->rounding_adjustment = $rounding;
        $this->grand_total = $grandTotal;
        $this->balance_amount = $grandTotal - $this->paid_amount;

        return $this;
    }

    public function updatePaymentStatus(): void
    {
        if ($this->paid_amount >= $this->grand_total) {
            $this->payment_status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = 'partial';
        } else {
            $this->payment_status = 'pending';
        }
        $this->save();
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['draft', 'placed', 'confirmed']);
    }
}
