<?php

namespace App\GraphQL\Queries;

use App\Models\Shipment;

class GetShipmentInfo
{
    public function __invoke($_, array $args): ?array
    {
        $shipmentCode = $args['shipmentCode'];
        $shipment = Shipment::with('warehouseOrder', 'trackingHistory')->where('shipping_code', $shipmentCode)->first();

        if (!$shipment) {
            return null;
        }

        return [
            'id' => $shipment->id,
            'shippingCode' => $shipment->shipping_code,
            'warehouseOrderId' => $shipment->warehouse_order_id,
            'courierName' => $shipment->courier_name,
            'trackingNumber' => $shipment->tracking_number,
            'storeAddress' => $shipment->store_address,
            'status' => $shipment->status,
            'shippedAt' => $shipment->shipped_at?->toIso8601String(),
            'proofOfDelivery' => $shipment->proof_of_delivery,
            'trackingHistory' => $shipment->trackingHistory->map(fn($t) => [
                'status' => $t->status,
                'notes' => $t->notes,
                'location' => $t->location,
                'occurredAt' => $t->occurred_at->toIso8601String(),
            ])->toArray(),
            'createdAt' => $shipment->created_at->toIso8601String(),
        ];
    }
}
