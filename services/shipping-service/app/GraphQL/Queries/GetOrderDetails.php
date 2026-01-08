<?php

namespace App\GraphQL\Queries;

use App\Models\WarehouseOrder;

class GetOrderDetails
{
    public function __invoke($_, array $args): ?array
    {
        $orderCode = $args['orderCode'];
        $order = WarehouseOrder::with('items', 'shipment')->where('toko_order_code', $orderCode)->first();

        if (!$order) {
            return null;
        }

        return [
            'id' => $order->id,
            'tokoOrderCode' => $order->toko_order_code,
            'storeCode' => $order->store_code,
            'status' => $order->status,
            'priority' => $order->priority,
            'processedBy' => $order->processedBy ? [
                'id' => $order->processedBy->id,
                'username' => $order->processedBy->name,
                'name' => $order->processedBy->email,
            ] : null,
            'items' => $order->items->map(fn($item) => [
                'productCode' => $item->product_code,
                'productName' => $item->product_name,
                'quantity' => $item->quantity,
                'unitPrice' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'status' => $item->status,
            ])->toArray(),
            'shipment' => $order->shipment ? [
                'id' => $order->shipment->id,
                'shippingCode' => $order->shipment->shipping_code,
                'courierName' => $order->shipment->courier_name ?? null,
                'trackingNumber' => $order->shipment->tracking_number ?? null,
                'storeAddress' => $order->shipment->store_address,
                'status' => $order->shipment->status,
                'shippedAt' => $order->shipment->shipped_at?->toIso8601String(),
                'proofOfDelivery' => $order->shipment->proof_of_delivery ?? null,
                'createdAt' => $order->shipment->created_at->toIso8601String(),
            ] : null,
            'totalAmount' => (float) $order->total_amount,
            'createdAt' => $order->created_at->toIso8601String(),
            'processedAt' => $order->processed_at?->toIso8601String(),
            'estimatedDelivery' => $order->estimated_delivery?->toIso8601String(),
        ];
    }
}
