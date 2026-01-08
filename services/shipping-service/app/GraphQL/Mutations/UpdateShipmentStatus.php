<?php

namespace App\GraphQL\Mutations;

use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Models\WarehouseOrder;

class UpdateShipmentStatus
{
    public function __invoke($_, array $args): array
    {
        $shipmentCode = $args['shippingCode'];
        $newStatus = $args['status'];
        $notes = $args['notes'] ?? null;
        $location = $args['location'] ?? null;

        $shipment = Shipment::where('shipping_code', $shipmentCode)->first();

        if (!$shipment) {
            return [
                'success' => false,
                'message' => 'Shipment not found',
                'shipment' => null,
            ];
        }

        // Valid status transitions
        $validStatuses = ['DIKEMAS', 'SIAP_DIKIRIM', 'DIKIRIM', 'DITERIMA_TOKO', 'DITOLAK_TOKO', 'HILANG'];
        if (!in_array($newStatus, $validStatuses)) {
            return [
                'success' => false,
                'message' => 'Invalid shipment status',
                'shipment' => null,
            ];
        }

        // Create tracking history
        ShipmentTracking::create([
            'shipment_id' => $shipment->id,
            'status' => $newStatus,
            'notes' => $notes ?? "Status updated to {$newStatus}",
            'location' => $location,
            'occurred_at' => now(),
        ]);

        // Update shipment
        $updateData = ['status' => $newStatus];
        if ($newStatus === 'DIKIRIM') {
            $updateData['shipped_at'] = now();
        }
        if ($newStatus === 'DITERIMA_TOKO' && $location) {
            $updateData['proof_of_delivery'] = $location; // Store proof reference
        }

        $shipment->update($updateData);

        // Update related warehouse order status
        $order = $shipment->warehouseOrder;
        if ($newStatus === 'DITERIMA_TOKO') {
            $order->update(['status' => 'DITERIMA_TOKO']);
        } elseif ($newStatus === 'DITOLAK_TOKO') {
            $order->update(['status' => 'DITOLAK']);
        }

        return [
            'success' => true,
            'message' => 'Shipment status updated successfully',
            'shipment' => $this->formatShipment($shipment->fresh()),
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
            'shippedAt' => $shipment->shipped_at?->toIso8601String(),
            'proofOfDelivery' => $shipment->proof_of_delivery,
            'updatedAt' => $shipment->updated_at->toIso8601String(),
        ];
    }
}
