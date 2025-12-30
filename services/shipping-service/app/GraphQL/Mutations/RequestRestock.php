<?php

namespace App\GraphQL\Mutations;

use App\Models\WarehouseOrder;
use Illuminate\Support\Facades\Http;
use GraphQL\Error\Error;

class RequestRestock
{
    /**
     * Request restock from Toko (External API)
     * This endpoint is called by Toko system with API Key authentication
     *
     * @param null $_
     * @param array{input: array} $args
     * @return array
     */
    public function __invoke($_, array $args): array
    {
        $input = $args['input'];

        try {
            // 1. Validate input
            if ($input['quantity'] <= 0) {
                throw new Error('Quantity must be greater than 0');
            }

            // 2. Check stock availability at Stock Service
            $stockServiceUrl = env('STOCK_SERVICE_URL', 'http://stock-service:8000/graphql');
            
            $stockQuery = <<<'GRAPHQL'
            query CheckStock($productCode: String!) {
              checkStock(productCode: $productCode) {
                productCode
                productName
                stock
              }
            }
            GRAPHQL;

            $stockResponse = Http::post($stockServiceUrl, [
                'query' => $stockQuery,
                'variables' => [
                    'productCode' => $input['productCode']
                ]
            ]);

            if ($stockResponse->failed()) {
                throw new Error('Failed to check stock availability');
            }

            $stockData = $stockResponse->json();
            $inventory = $stockData['data']['checkStock'] ?? null;

            if (!$inventory) {
                return [
                    'success' => false,
                    'orderCode' => null,
                    'estimatedDelivery' => null,
                    'message' => 'Product not found in warehouse inventory',
                ];
            }

            if ($inventory['stock'] < $input['quantity']) {
                return [
                    'success' => false,
                    'orderCode' => null,
                    'estimatedDelivery' => null,
                    'message' => "Insufficient stock. Available: {$inventory['stock']}, Requested: {$input['quantity']}",
                ];
            }

            // 3. Create warehouse order with status MENUNGGU
            $orderCode = 'WH-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

            $warehouseOrder = WarehouseOrder::create([
                'toko_order_code' => $orderCode,
                'product_code' => $input['productCode'],
                'quantity' => $input['quantity'],
                'status' => 'MENUNGGU',
                'user_id' => null, // Will be assigned when staff processes it
            ]);

            // 4. Calculate estimated delivery (2-3 days from now)
            $estimatedDelivery = date('Y-m-d', strtotime('+3 days'));

            return [
                'success' => true,
                'orderCode' => $orderCode,
                'estimatedDelivery' => $estimatedDelivery,
                'message' => 'Restock request created successfully. Awaiting warehouse approval.',
            ];

        } catch (Error $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new Error('Failed to process restock request: ' . $e->getMessage());
        }
    }
}
