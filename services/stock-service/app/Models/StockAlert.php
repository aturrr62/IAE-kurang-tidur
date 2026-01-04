<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model StockAlert
 * 
 * Sistem monitoring real-time untuk stok rendah/habis
 */
class StockAlert extends Model
{
    use HasFactory;

    protected $table = 'stock_alerts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_code',
        'alert_type',
        'current_stock',
        'resolved',
        'resolved_at',
        'resolved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_stock' => 'integer',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Alert type constants
     */
    const TYPE_LOW = 'low';
    const TYPE_OUT = 'out';

    /**
     * Relationships: Inventory item
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'product_code', 'product_code');
    }

    /**
     * Relationships: Staff who resolved the alert
     */
    public function resolver()
    {
        return $this->belongsTo(WarehouseStaff::class, 'resolved_by');
    }

    /**
     * Scope: Only unresolved alerts
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope: Only resolved alerts
     */
    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    /**
     * Scope: Low stock alerts
     */
    public function scopeLowStock($query)
    {
        return $query->where('alert_type', self::TYPE_LOW);
    }

    /**
     * Scope: Out of stock alerts
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('alert_type', self::TYPE_OUT);
    }

    /**
     * Mark alert as resolved
     */
    public function resolve(?int $staffId = null): bool
    {
        $this->resolved = true;
        $this->resolved_at = now();
        $this->resolved_by = $staffId;
        return $this->save();
    }
}
