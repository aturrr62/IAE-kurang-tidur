<?php

namespace App\GraphQL\Mutations;

use App\Models\Inventory;
use App\Models\StockAlert;
use App\Models\StockTransaction;
use App\Services\JwtService;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

/**
 * StockMutation
 * 
 * Resolver untuk stock management mutations
 * Endpoint utama untuk perubahan stok dari Shipping Service
 */
class StockMutation
{
    protected JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Get authenticated user ID from context
     */
    protected function getAuthUserId(GraphQLContext $context): ?int
    {
        $request = $context->request();
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return null;
        }

        $token = $this->jwtService->extractTokenFromHeader($authHeader);
        $user = $this->jwtService->getAuthenticatedUser($token);
        
        return $user?->id;
    }

    /**
     * Mutation: updateStock
     * 
     * Update stock dengan berbagai action (INCREMENT, DECREMENT, SET, dll)
     * Mencatat transaksi untuk audit trail
     * 
     * @return Inventory
     */
    public function updateStock($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Inventory
    {
        $input = $args['input'];
        $productCode = $input['productCode'];
        $quantity = $input['quantity'];
        $action = $input['action'];
        $note = $input['note'] ?? null;
        $staffId = $this->getAuthUserId($context);

        try {
            // Record transaction (akan otomatis update inventory)
            StockTransaction::recordTransaction(
                $productCode,
                $quantity,
                $action,
                $note,
                $staffId
            );

            // Return updated inventory
            return Inventory::where('product_code', $productCode)->firstOrFail();

        } catch (\Exception $e) {
            throw new \Exception("Failed to update stock: " . $e->getMessage());
        }
    }

    /**
     * Mutation: reserveStock
     * 
     * Reserve stock untuk order processing (dari Shipping Service)
     * Menggunakan action RESERVE untuk mengurangi stok
     * 
     * @return array StockCheckResult
     */
    public function reserveStock($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        $input = $args['input'];
        $productCode = $input['productCode'];
        $quantity = $input['quantity'];
        $orderId = $input['orderId'] ?? null;
        $staffId = $this->getAuthUserId($context);

        try {
            // Check availability first
            $inventory = Inventory::where('product_code', $productCode)->first();

            if (!$inventory) {
                throw new \Exception("Product not found: {$productCode}");
            }

            if (!$inventory->isAvailable($quantity)) {
                return [
                    'productCode' => $productCode,
                    'productName' => $inventory->product_name,
                    'available' => false,
                    'currentStock' => $inventory->current_stock,
                    'requestedQuantity' => $quantity,
                    'message' => "Insufficient stock. Available: {$inventory->current_stock}, Requested: {$quantity}",
                ];
            }

            // Reserve stock
            $note = $orderId ? "Reserved for order: {$orderId}" : "Stock reserved";
            
            StockTransaction::recordTransaction(
                $productCode,
                $quantity,
                StockTransaction::ACTION_RESERVE,
                $note,
                $staffId
            );

            // Get updated inventory
            $inventory->refresh();

            return [
                'productCode' => $inventory->product_code,
                'productName' => $inventory->product_name,
                'available' => true,
                'currentStock' => $inventory->current_stock,
                'requestedQuantity' => $quantity,
                'message' => "Stock reserved successfully",
            ];

        } catch (\Exception $e) {
            throw new \Exception("Failed to reserve stock: " . $e->getMessage());
        }
    }

    /**
     * Mutation: releaseStock
     * 
     * Release reserved stock (cancel order)
     * Menggunakan action RELEASE untuk menambah kembali stok
     * 
     * @return Inventory
     */
    public function releaseStock($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Inventory
    {
        $productCode = $args['productCode'];
        $quantity = $args['quantity'];
        $orderId = $args['orderId'] ?? null;
        $staffId = $this->getAuthUserId($context);

        try {
            $note = $orderId ? "Released from cancelled order: {$orderId}" : "Stock released";

            StockTransaction::recordTransaction(
                $productCode,
                $quantity,
                StockTransaction::ACTION_RELEASE,
                $note,
                $staffId
            );

            return Inventory::where('product_code', $productCode)->firstOrFail();

        } catch (\Exception $e) {
            throw new \Exception("Failed to release stock: " . $e->getMessage());
        }
    }

    /**
     * Mutation: adjustStock
     * 
     * Adjust stock directly to specific value (admin/manager only)
     * Menggunakan action SET
     * 
     * @return Inventory
     */
    public function adjustStock($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Inventory
    {
        $productCode = $args['productCode'];
        $newStock = $args['newStock'];
        $note = $args['note'] ?? 'Manual stock adjustment';
        $staffId = $this->getAuthUserId($context);

        // Check authorization (bisa ditambahkan role check)
        $request = $context->request();
        $authHeader = $request->header('Authorization');
        
        if ($authHeader) {
            $token = $this->jwtService->extractTokenFromHeader($authHeader);
            $user = $this->jwtService->getAuthenticatedUser($token);
            
            if ($user && !$user->isAdmin() && !$user->isManager()) {
                throw new \Exception('Unauthorized. Admin or Manager access required.');
            }
        }

        try {
            StockTransaction::recordTransaction(
                $productCode,
                $newStock,
                StockTransaction::ACTION_SET,
                $note,
                $staffId
            );

            return Inventory::where('product_code', $productCode)->firstOrFail();

        } catch (\Exception $e) {
            throw new \Exception("Failed to adjust stock: " . $e->getMessage());
        }
    }

    /**
     * Mutation: resolveAlert
     * 
     * Mark stock alert as resolved
     * 
     * @return StockAlert
     */
    public function resolveAlert($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): StockAlert
    {
        $alertId = $args['alertId'];
        $staffId = $this->getAuthUserId($context);

        $alert = StockAlert::find($alertId);

        if (!$alert) {
            throw new \Exception('Alert not found');
        }

        if ($alert->resolved) {
            throw new \Exception('Alert already resolved');
        }

        $alert->resolve($staffId);

        return $alert->fresh();
    }

    /**
     * Mutation: increaseStock
     * 
     * Shortcut method untuk menambah stock (README compliance)
     * Menggunakan action INCREMENT
     * 
     * @return Inventory
     */
    public function increaseStock($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Inventory
    {
        $productCode = $args['productCode'];
        $quantity = $args['quantity'];
        $staffId = $this->getAuthUserId($context);

        try {
            StockTransaction::recordTransaction(
                $productCode,
                $quantity,
                StockTransaction::ACTION_INCREMENT,
                'Stock increased via increaseStock mutation',
                $staffId
            );

            return Inventory::where('product_code', $productCode)->firstOrFail();

        } catch (\Exception $e) {
            throw new \Exception("Failed to increase stock: " . $e->getMessage());
        }
    }

    /**
     * Mutation: decreaseStock
     * 
     * Shortcut method untuk mengurangi stock (README compliance)
     * Menggunakan action DECREMENT
     * 
     * @return Inventory
     */
    public function decreaseStock($_, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Inventory
    {
        $productCode = $args['productCode'];
        $quantity = $args['quantity'];
        $staffId = $this->getAuthUserId($context);

        try {
            StockTransaction::recordTransaction(
                $productCode,
                $quantity,
                StockTransaction::ACTION_DECREMENT,
                'Stock decreased via decreaseStock mutation',
                $staffId
            );

            return Inventory::where('product_code', $productCode)->firstOrFail();

        } catch (\Exception $e) {
            throw new \Exception("Failed to decrease stock: " . $e->getMessage());
        }
    }
}
