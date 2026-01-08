<?php

namespace App\GraphQL\Queries;

use App\Models\WarehouseOrder;

class PendingOrders
{
    public function __invoke($_, array $args): array
    {
        $priority = $args['priority'] ?? null;

        $query = WarehouseOrder::whereIn('status', ['MENUNGGU', 'DITERIMA', 'DIPROSES', 'DIKEMAS']);

        if ($priority) {
            $query->where('priority', $priority);
        }

        $orders = $query->with('items', 'shipment')->orderBy('priority', 'desc')->orderBy('created_at', 'asc')->get();

        return $orders->map(fn($order) => [
            'id' => $order->id,
            'tokoOrderCode' => $order->toko_order_code,
            'storeCode' => $order->store_code,
            'status' => $order->status,
            'priority' => $order->priority,
            'itemCount' => $order->items->count(),
            'createdAt' => $order->created_at->toIso8601String(),
        ])->toArray();
    }
}
