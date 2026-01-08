<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentTracking extends Model
{
    use HasFactory;

    protected $table = 'shipment_tracking';

    protected $fillable = ['shipment_id', 'status', 'notes', 'location', 'occurred_at'];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
}
