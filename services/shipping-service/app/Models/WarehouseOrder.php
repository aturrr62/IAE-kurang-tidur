<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'toko_order_code',
        'store_code',
        'status',
        'processed_by',
        'notes',
        'rejection_reason',
        'total_amount',
        'priority',
        'processed_at',
        'estimated_delivery',
    ];

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
