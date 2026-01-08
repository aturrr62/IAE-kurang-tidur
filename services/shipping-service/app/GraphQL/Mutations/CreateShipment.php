<?php

namespace App\GraphQL\Mutations;

use App\Models\WarehouseOrder;
use App\Models\Shipment;

class CreateShipment
{
    public function __invoke($_, array $args): array
    {
        $input = $args['input'];
        $orderId = $input['warehouseOrderId'];

        $order = WarehouseOrder::find($orderId);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Warehouse order not found',
                'shipment' => null,
            ];
        }

        if ($order->status !== 'DIPROSES') {
            return [
                'success' => false,
                'message' => 'Order must be in DIPROSES status to create shipment',
                'shipment' => null,
            ];
        }

        // Generate unique shipping code
        $shippingCode = 'SHIP-' . strtoupper(uniqid());

        $shipment = Shipment::create([
            'warehouse_order_id' => $orderId,
            'shipping_code' => $shippingCode,
            'courier_name' => $input['courierName'],
            'tracking_number' => $input['trackingNumber'] ?? null,
            'store_address' => $input['storeAddress'],
            'status' => 'DIKEMAS',
        ]);

        // Update order status
        $order->update(['status' => 'DIKEMAS']);

        return [
            'success' => true,
            'message' => 'Shipment created successfully',
            'shipment' => $this->formatShipment($shipment),
        ];
    }

    private function formatShipment(Shipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'shippingCode' => $shipment->shipping_code,
            'warehouseOrderId' => $shipment->warehouse_order_id,
            'courierName' => $shipment->courier_name,
            'trackingNumber' => $shipment->tracking_number,
            'storeAddress' => $shipment->store_address,
            'status' => $shipment->status,
            'createdAt' => $shipment->created_at->toIso8601String(),
        ];
    }
}
