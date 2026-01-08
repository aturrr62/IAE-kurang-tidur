<?php

namespace App\GraphQL\Mutations;

use App\Models\WarehouseOrder;

class RejectWarehouseOrder
{
    public function __invoke($_, array $args): array
    {
        $orderId = $args['orderId'];
        $reason = $args['reason'];
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
                'message' => 'Only MENUNGGU orders can be rejected',
                'order' => null,
            ];
        }

        $authUser = request()->user();
        $order->update([
            'status' => 'DITOLAK',
            'rejection_reason' => $reason,
            'processed_by' => $authUser->id,
            'processed_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Order rejected successfully',
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
            'rejectionReason' => $order->rejection_reason,
            'createdAt' => $order->created_at->toIso8601String(),
        ];
    }
}
