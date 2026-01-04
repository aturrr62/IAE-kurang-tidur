<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Inventory
 * 
 * Master data barang dengan tracking stok real-time
 * Single source of truth untuk ketersediaan stok
 */
class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_code',
        'product_name',
        'current_stock',
        'min_stock_level',
        'max_stock_level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_stock' => 'integer',
        'min_stock_level' => 'integer',
        'max_stock_level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method untuk event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-check untuk low stock setelah update
        static::updated(function ($inventory) {
            $inventory->checkStockLevel();
        });

        static::created(function ($inventory) {
            $inventory->checkStockLevel();
        });
    }

    /**
     * Check if stock is low (below minimum)
     */
    public function isLowStock(): bool
    {
        return $this->current_stock > 0 && $this->current_stock <= $this->min_stock_level;
    }

    /**
     * Check if stock is out
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Check if stock is available
     */
    public function isAvailable(int $quantity = 1): bool
    {
        return $this->current_stock >= $quantity;
    }

    /**
     * Check stock level and create alert if needed
     */
    public function checkStockLevel(): void
    {
        if ($this->isOutOfStock()) {
            $this->createAlert('out');
        } elseif ($this->isLowStock()) {
            $this->createAlert('low');
        } else {
            // Resolve alerts if stock is back to normal
            $this->resolveAlerts();
        }
    }

    /**
     * Create stock alert
     */
    private function createAlert(string $type): void
    {
        // Check if unresolved alert already exists
        $existingAlert = StockAlert::where('product_code', $this->product_code)
            ->where('alert_type', $type)
            ->where('resolved', false)
            ->first();

        if (!$existingAlert) {
            StockAlert::create([
                'product_code' => $this->product_code,
                'alert_type' => $type,
                'current_stock' => $this->current_stock,
                'resolved' => false,
            ]);
        }
    }

    /**
     * Resolve all alerts for this product
     */
    private function resolveAlerts(): void
    {
        StockAlert::where('product_code', $this->product_code)
            ->where('resolved', false)
            ->update([
                'resolved' => true,
                'resolved_at' => now(),
            ]);
    }

    /**
     * Relationships: Stock transactions
     */
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'product_code', 'product_code');
    }

    /**
     * Relationships: Stock alerts
     */
    public function alerts()
    {
        return $this->hasMany(StockAlert::class, 'product_code', 'product_code');
    }

    /**
     * Get unresolved alerts
     */
    public function unresolvedAlerts()
    {
        return $this->alerts()->where('resolved', false);
    }
}
