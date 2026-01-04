<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model WarehouseStaff
 * 
 * Representasi staff gudang dengan role-based access
 * Merupakan authentication provider untuk Stock Service
 */
class WarehouseStaff extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'warehouse_staff';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'role',
        'department',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method untuk event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically hash password when creating/updating
        static::creating(function ($staff) {
            if (isset($staff->password)) {
                $staff->password = bcrypt($staff->password);
            }
        });

        static::updating(function ($staff) {
            if ($staff->isDirty('password')) {
                $staff->password = bcrypt($staff->password);
            }
        });
    }

    /**
     * Check if staff is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if staff is manager
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if staff handles inventory
     */
    public function handlesInventory(): bool
    {
        return in_array($this->department, ['inventory', 'both']);
    }

    /**
     * Check if staff handles shipping
     */
    public function handlesShipping(): bool
    {
        return in_array($this->department, ['shipping', 'both']);
    }

    /**
     * Relationships: Stock transactions performed by this staff
     */
    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'staff_id');
    }

    /**
     * Relationships: Alerts resolved by this staff
     */
    public function resolvedAlerts()
    {
        return $this->hasMany(StockAlert::class, 'resolved_by');
    }
}
