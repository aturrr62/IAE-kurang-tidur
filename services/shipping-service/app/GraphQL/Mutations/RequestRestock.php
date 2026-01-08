<?php

namespace App\GraphQL\Mutations;

use App\Models\WarehouseOrder;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Http;

class RequestRestock
{
    /**
     * Request restock from Toko (External API)
     * This endpoint is called by Toko system with API Key authentication
     *
    * @param mixed $_
     * @param array{input: array} $args
     * @return array
     */
    public function __invoke($_, array $args): array
    {
        $input = $args['input'];

        try {
            // Basic validation
            if (empty($input['storeId']) || empty($input['items']) || !is_array($input['items'])) {
                throw new \Exception('Invalid input: storeId and items are required');
            }

            $stockServiceUrl = \env('STOCK_SERVICE_URL', 'http://stock-service:8003/graphql');
            $authToken = \env('STOCK_SERVICE_JWT');

            $processed = [];
            $failed = [];

            // Validate each item against Stock Service
            foreach ($input['items'] as $item) {
                if (empty($item['productCode']) || empty($item['quantity']) || $item['quantity'] <= 0) {
                    $failed[] = [
                        'productCode' => $item['productCode'] ?? null,
                        'quantity' => $item['quantity'] ?? 0,
                        'reason' => 'Invalid productCode or quantity',
                    ];
                    continue;
                }

                $stockQuery = <<<'GRAPHQL'
                query CheckStock($productCode: String!, $quantity: Int!) {
                  checkStock(productCode: $productCode, quantity: $quantity) {
                    available
                    currentStock
                    message
                  }
                }
                GRAPHQL;

                $headers = ['Content-Type' => 'application/json'];
                if ($authToken) {
                    $headers['Authorization'] = 'Bearer ' . $authToken;
                }

                $resp = \Illuminate\Support\Facades\Http::withHeaders($headers)->post($stockServiceUrl, [
                    'query' => $stockQuery,
                    'variables' => [
                        'productCode' => $item['productCode'],
                        'quantity' => (int) $item['quantity'],
                    ],
                ]);

                if ($resp->failed()) {
                    $failed[] = [
                        'productCode' => $item['productCode'],
                        'quantity' => $item['quantity'],
                        'reason' => 'Stock service unreachable',
                    ];
                    continue;
                }

                $body = $resp->json();
                $check = $body['data']['checkStock'] ?? null;

                if (!$check || ($check['available'] ?? false) === false) {
                    $failed[] = [
                        'productCode' => $item['productCode'],
                        'quantity' => $item['quantity'],
                        'reason' => $check['message'] ?? 'Not available',
                    ];
                    continue;
                }

                $processed[] = [
                    'productCode' => $item['productCode'],
                    'quantity' => $item['quantity'],
                    'status' => 'RESERVED',
                ];
            }

            if (empty($processed)) {
                return [
                    'success' => false,
                    'orderId' => null,
                    'estimatedDelivery' => null,
                    'message' => 'No items are available to create an order',
                    'processedItems' => [],
                    'failedItems' => $failed,
                ];
            }

            // Create warehouse_order
            $order = WarehouseOrder::create([
                'toko_order_code' => 'WH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'store_code' => $input['storeId'],
                'status' => 'MENUNGGU',
                'priority' => 'NORMAL',
            ]);

            // Create order_items
            foreach ($processed as $p) {
                OrderItem::create([
                    'warehouse_order_id' => $order->id,
                    'product_code' => $p['productCode'],
                    'product_name' => null,
                    'quantity' => $p['quantity'],
                    'unit_price' => 0,
                    'subtotal' => 0,
                    'status' => 'RESERVED',
                ]);
            }

            $estimatedDelivery = \date('Y-m-d', \strtotime('+3 days'));

            return [
                'success' => true,
                'orderId' => $order->id,
                'estimatedDelivery' => $estimatedDelivery,
                'message' => 'Restock request created successfully. Awaiting warehouse approval.',
                'processedItems' => $processed,
                'failedItems' => $failed,
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }
}
