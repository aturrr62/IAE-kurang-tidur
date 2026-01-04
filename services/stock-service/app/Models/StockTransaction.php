<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model StockTransaction
 * 
 * Audit trail untuk setiap perubahan stok
 * Menyimpan siapa, kapan, dan apa yang dilakukan terhadap stok
 */
class StockTransaction extends Model
{
    use HasFactory;

    protected $table = 'stock_transactions';

    // Disable updated_at karena ini audit log (immutable)
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_code',
        'quantity',
        'action',
        'note',
        'staff_id',
        'stock_before',
        'stock_after',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Stock action constants
     */
    const ACTION_INCREMENT = 'INCREMENT';
    const ACTION_DECREMENT = 'DECREMENT';
    const ACTION_SET = 'SET';
    const ACTION_RESERVE = 'RESERVE';
    const ACTION_RELEASE = 'RELEASE';

    /**
     * Relationships: Inventory item
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'product_code', 'product_code');
    }

    /**
     * Relationships: Staff who performed the transaction
     */
    public function staff()
    {
        return $this->belongsTo(WarehouseStaff::class, 'staff_id');
    }

    /**
     * Create transaction and update stock atomically
     * 
     * @param string $productCode
     * @param int $quantity
     * @param string $action
     * @param string|null $note
     * @param int|null $staffId
     * @return static
     * @throws \Exception
     */
    public static function recordTransaction(
        string $productCode,
        int $quantity,
        string $action,
        ?string $note = null,
        ?int $staffId = null
    ): self {
        return \DB::transaction(function () use ($productCode, $quantity, $action, $note, $staffId) {
            // Lock inventory row for update
            $inventory = Inventory::where('product_code', $productCode)->lockForUpdate()->first();

            if (!$inventory) {
                throw new \Exception("Product not found: {$productCode}");
            }

            $stockBefore = $inventory->current_stock;
            $stockAfter = $stockBefore;

            // Calculate new stock based on action
            switch ($action) {
                case self::ACTION_INCREMENT:
                    $stockAfter = $stockBefore + $quantity;
                    break;
                case self::ACTION_DECREMENT:
                case self::ACTION_RESERVE:
                    if ($stockBefore < $quantity) {
                        throw new \Exception("Insufficient stock. Available: {$stockBefore}, Requested: {$quantity}");
                    }
                    $stockAfter = $stockBefore - $quantity;
                    break;
                case self::ACTION_RELEASE:
                    $stockAfter = $stockBefore + $quantity;
                    break;
                case self::ACTION_SET:
                    $stockAfter = $quantity;
                    break;
                default:
                    throw new \Exception("Invalid action: {$action}");
            }

            // Validate stock doesn't exceed max
            if ($stockAfter > $inventory->max_stock_level) {
                throw new \Exception("Stock would exceed maximum capacity: {$inventory->max_stock_level}");
            }

            // Update inventory
            $inventory->current_stock = $stockAfter;
            $inventory->save();

            // Create transaction record
            return self::create([
                'product_code' => $productCode,
                'quantity' => $quantity,
                'action' => $action,
                'note' => $note,
                'staff_id' => $staffId,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
            ]);
        });
    }
}
