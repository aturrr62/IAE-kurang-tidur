<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = ['warehouse_order_id', 'shipping_code', 'store_address', 'shipped_at', 'status'];

    public function warehouseOrder()
    {
        return $this->belongsTo(WarehouseOrder::class);
    }
}
