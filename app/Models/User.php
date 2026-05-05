<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'restaurant_id', 'role_id', 'name', 'email', 'password', 'phone',
        'employee_code', 'address', 'avatar', 'date_of_birth', 'date_of_joining',
        'salary', 'shift_start', 'shift_end', 'is_active', 'pin_code',
    ];

    protected $hidden = ['password', 'remember_token', 'pin_code'];

    protected $casts = [
        'is_active' => 'boolean',
        'date_of_birth' => 'date',
        'date_of_joining' => 'date',
        'email_verified_at' => 'datetime',
        'salary' => 'decimal:2',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->role->permissions()->where('slug', $permissionSlug)->exists();
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->role->permissions()->whereIn('slug', $permissions)->exists();
    }

    public function ordersCreated()
    {
        return $this->hasMany(Order::class, 'created_by');
    }
}
