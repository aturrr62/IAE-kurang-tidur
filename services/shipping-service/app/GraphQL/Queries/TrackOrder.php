<?php

namespace App\GraphQL\Queries;

use App\Models\WarehouseOrder;


class TrackOrder
{
    /**
     * Track order for Toko (External API)
     * Returns tracking status and events
     *
    * @param mixed $_
     * @param array{orderCode: string} $args
     * @return array
     */
    public function __invoke($_, array $args): array
    {
        $orderCode = $args['orderCode'];

        // Find warehouse order by toko_order_code
        $order = WarehouseOrder::with('shipment')
            ->where('toko_order_code', $orderCode)
            ->first();

        if (!$order) {
            throw new \Exception('Order not found');
        }

        // Build tracking events based on status
        $events = [];
        
        // Event 1: Order created
        $events[] = [
            'timestamp' => $order->created_at->toIso8601String(),
            'description' => 'Restock request received',
            'status' => 'MENUNGGU',
        ];

        // Event 2: Order approved/rejected
        if ($order->status === 'DITERIMA' || $order->status === 'DITOLAK') {
            $statusText = $order->status === 'DITERIMA' ? 'approved' : 'rejected';
                $events[] = [
                    'timestamp' => $order->updated_at->toIso8601String(),
                'description' => "Order {$statusText} by warehouse staff",
                'status' => $order->status,
            ];
        }

        // Event 3: Shipment created
        if ($order->shipment) {
            $events[] = [
                'timestamp' => $order->shipment->created_at->toIso8601String(),
                'description' => "Shipment created with code: {$order->shipment->shipping_code}",
                'status' => 'SIAP_DIKIRIM',
            ];

            // Event 4: Shipped
            if ($order->shipment->status === 'DIKIRIM' || $order->shipment->status === 'DITERIMA_TOKO') {
                $events[] = [
                    'timestamp' => $order->shipment->shipped_at ?? $order->shipment->updated_at->toIso8601String(),
                    'description' => 'Package shipped to store',
                    'status' => 'DIKIRIM',
                ];
            }

            // Event 5: Delivered
            if ($order->shipment->status === 'DITERIMA_TOKO') {
                $events[] = [
                    'timestamp' => $order->shipment->updated_at->toIso8601String(),
                    'description' => 'Package delivered to store',
                    'status' => 'DITERIMA_TOKO',
                ];
            }
        }

        // Calculate estimated delivery
        $estimatedDelivery = null;
        if ($order->status === 'DITERIMA' && (!$order->shipment || $order->shipment->status !== 'DITERIMA_TOKO')) {
            $estimatedDelivery = date('Y-m-d', strtotime($order->created_at . ' +3 days'));
        }

        return [
            'orderCode' => $orderCode,
            'status' => $order->shipment?->status ?? $order->status,
            'estimatedDelivery' => $estimatedDelivery,
            'events' => $events,
        ];
    }
}
