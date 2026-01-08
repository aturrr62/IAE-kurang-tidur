<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['warehouse_order_id', 'product_code', 'product_name', 'quantity', 'unit_price', 'subtotal', 'status'];

    public function warehouseOrder()
    {
        return $this->belongsTo(WarehouseOrder::class);
    }
}
