<?php

namespace App\GraphQL\Queries;

use App\Models\Inventory;
use App\Models\StockAlert;
use App\Models\StockTransaction;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * StockQuery
 * 
 * Resolver untuk stock & inventory queries
 * Endpoint utama untuk integrasi dengan Shipping Service dan Product Service
 */
class StockQuery
{
    /**
     * Query: checkStock
     * 
     * Check single product stock availability
     * Endpoint untuk Shipping Service dan Product Service (eksternal)
     * 
     * @return array StockCheckResult
     */
    public function checkStock($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $productCode = $args['productCode'];
        $quantity = $args['quantity'] ?? 1;

        $inventory = Inventory::where('product_code', $productCode)->first();

        if (!$inventory) {
            return [
                'productCode' => $productCode,
                'productName' => null,
                'available' => false,
                'currentStock' => 0,
                'requestedQuantity' => $quantity,
                'message' => 'Product not found',
            ];
        }

        $available = $inventory->isAvailable($quantity);

        return [
            'productCode' => $inventory->product_code,
            'productName' => $inventory->product_name,
            'available' => $available,
            'currentStock' => $inventory->current_stock,
            'requestedQuantity' => $quantity,
            'message' => $available 
                ? 'Stock available' 
                : "Insufficient stock. Available: {$inventory->current_stock}, Requested: {$quantity}",
        ];
    }

    /**
     * Query: bulkCheckStock
     * 
     * Bulk check multiple products stock
     * Untuk Product Service (eksternal) yang butuh check banyak produk sekaligus
     * 
     * @return array BulkStockCheckResult
     */
    public function bulkCheckStock($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $items = $args['items'];
        $results = [];
        $allAvailable = true;

        foreach ($items as $item) {
            $productCode = $item['productCode'];
            $quantity = $item['quantity'] ?? 1;

            $inventory = Inventory::where('product_code', $productCode)->first();

            if (!$inventory) {
                $results[] = [
                    'productCode' => $productCode,
                    'productName' => null,
                    'available' => false,
                    'currentStock' => 0,
                    'requestedQuantity' => $quantity,
                    'message' => 'Product not found',
                ];
                $allAvailable = false;
                continue;
            }

            $available = $inventory->isAvailable($quantity);

            $results[] = [
                'productCode' => $inventory->product_code,
                'productName' => $inventory->product_name,
                'available' => $available,
                'currentStock' => $inventory->current_stock,
                'requestedQuantity' => $quantity,
                'message' => $available 
                    ? 'Stock available' 
                    : "Insufficient stock. Available: {$inventory->current_stock}, Requested: {$quantity}",
            ];

            if (!$available) {
                $allAvailable = false;
            }
        }

        return [
            'results' => $results,
            'allAvailable' => $allAvailable,
        ];
    }

    /**
     * Query: inventoryList
     * 
     * List all inventory items with optional filters
     */
    public function inventoryList($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $query = Inventory::query();

        // Search filter
        if (isset($args['search']) && !empty($args['search'])) {
            $search = $args['search'];
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'LIKE', "%{$search}%")
                  ->orWhere('product_name', 'LIKE', "%{$search}%");
            });
        }

        // Low stock filter
        if (isset($args['lowStockOnly']) && $args['lowStockOnly']) {
            $query->whereRaw('current_stock <= min_stock_level');
        }

        return $query->orderBy('product_name')->get()->toArray();
    }

    /**
     * Query: inventories
     * 
     * Alias untuk inventoryList (untuk kompatibilitas dengan README)
     * Get all inventory items
     * 
     * @return array
     */
    public function inventories($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        return Inventory::orderBy('product_name')->get()->toArray();
    }

    /**
     * Query: lowStockAlerts
     * 
     * Get stock alerts (low/out of stock)
     */
    public function lowStockAlerts($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $resolved = $args['resolved'] ?? false;

        $query = StockAlert::with(['inventory', 'resolver']);

        if ($resolved !== null) {
            $query->where('resolved', $resolved);
        }

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Query: stockTransactions
     * 
     * Get stock transaction history
     */
    public function stockTransactions($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $query = StockTransaction::with(['inventory', 'staff']);

        // Filter by product code
        if (isset($args['productCode'])) {
            $query->where('product_code', $args['productCode']);
        }

        // Filter by action
        if (isset($args['action'])) {
            $query->where('action', $args['action']);
        }

        // Limit
        $limit = $args['limit'] ?? 50;
        $query->limit($limit);

        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }
}
