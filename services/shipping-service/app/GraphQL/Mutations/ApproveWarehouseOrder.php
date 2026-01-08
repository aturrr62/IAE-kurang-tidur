<?php

namespace App\GraphQL\Mutations;

use App\Models\WarehouseOrder;
use App\Models\ShipmentTracking;

class ApproveWarehouseOrder
{
    public function __invoke($_, array $args): array
    {
        $orderId = $args['orderId'];
        $order = WarehouseOrder::find($orderId);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found',
                'order' => null,
            ];
        }

        if ($order->status !== 'MENUNGGU') {
            return [
                'success' => false,
                'message' => 'Order status is not MENUNGGU',
                'order' => null,
            ];
        }

        $authUser = request()->user();
        $order->update([
            'status' => 'DITERIMA',
            'processed_by' => $authUser->id,
            'processed_at' => now(),
        ]);

        // Record tracking event
        ShipmentTracking::create([
            'shipment_id' => $order->shipment_id,
            'status' => 'DITERIMA',
            'notes' => "Order approved by {$authUser->name}",
            'occurred_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Order approved successfully',
            'order' => $this->formatOrder($order),
        ];
    }

    private function formatOrder(WarehouseOrder $order): array
    {
        return [
            'id' => $order->id,
            'tokoOrderCode' => $order->toko_order_code,
            'storeCode' => $order->store_code,
            'status' => $order->status,
            'priority' => $order->priority,
            'totalAmount' => $order->total_amount,
            'estimatedDelivery' => $order->estimated_delivery?->toDateString(),
            'createdAt' => $order->created_at->toIso8601String(),
        ];
    }
}
