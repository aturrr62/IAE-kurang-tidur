<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseOrder extends Model
{
    use HasFactory;

    protected $fillable = ['toko_order_code', 'product_code', 'quantity', 'status', 'user_id'];

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }
}
