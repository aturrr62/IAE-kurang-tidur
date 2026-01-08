<?php

namespace App\GraphQL\Queries;

use App\Models\WarehouseOrder;

class GetStoreOrders
{
    public function __invoke($_, array $args): array
    {
        $storeCode = $args['storeCode'];
        $status = $args['status'] ?? null;
        $fromDate = $args['fromDate'] ?? null;
        $toDate = $args['toDate'] ?? null;

        $query = WarehouseOrder::where('store_code', $storeCode);

        if ($status) {
            $query->where('status', $status);
        }

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $orders = $query->with('items')->orderBy('created_at', 'desc')->get();

        return $orders->map(fn($order) => [
            'orderId' => $order->id,
            'tokoOrderCode' => $order->toko_order_code,
            'status' => $order->status,
            'totalAmount' => (float) $order->total_amount,
            'createdAt' => $order->created_at->toIso8601String(),
            'itemCount' => $order->items->count(),
        ])->toArray();
    }
}
