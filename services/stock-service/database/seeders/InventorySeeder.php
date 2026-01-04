<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

/**
 * InventorySeeder
 * 
 * Seed 10 produk elektronik konsisten dengan data kelompok Toko
 * Data disesuaikan dengan typical electronic products
 */
class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'product_code' => 'ELECT-001',
                'product_name' => 'Laptop ASUS ROG Strix G15',
                'current_stock' => 50,
                'min_stock_level' => 10,
                'max_stock_level' => 200,
            ],
            [
                'product_code' => 'ELECT-002',
                'product_name' => 'Smartphone Samsung Galaxy S24 Ultra',
                'current_stock' => 75,
                'min_stock_level' => 15,
                'max_stock_level' => 300,
            ],
            [
                'product_code' => 'ELECT-003',
                'product_name' => 'Monitor LG UltraGear 27 inch 4K',
                'current_stock' => 30,
                'min_stock_level' => 8,
                'max_stock_level' => 150,
            ],
            [
                'product_code' => 'ELECT-004',
                'product_name' => 'Mechanical Keyboard Logitech G Pro X',
                'current_stock' => 100,
                'min_stock_level' => 20,
                'max_stock_level' => 500,
            ],
            [
                'product_code' => 'ELECT-005',
                'product_name' => 'Wireless Mouse Logitech MX Master 3S',
                'current_stock' => 120,
                'min_stock_level' => 25,
                'max_stock_level' => 600,
            ],
            [
                'product_code' => 'ELECT-006',
                'product_name' => 'Headphones Sony WH-1000XM5',
                'current_stock' => 60,
                'min_stock_level' => 12,
                'max_stock_level' => 250,
            ],
            [
                'product_code' => 'ELECT-007',
                'product_name' => 'Tablet iPad Pro 12.9 inch M2',
                'current_stock' => 40,
                'min_stock_level' => 10,
                'max_stock_level' => 180,
            ],
            [
                'product_code' => 'ELECT-008',
                'product_name' => 'Smartwatch Apple Watch Series 9',
                'current_stock' => 8, // Low stock (akan trigger alert)
                'min_stock_level' => 10,
                'max_stock_level' => 200,
            ],
            [
                'product_code' => 'ELECT-009',
                'product_name' => 'External SSD Samsung T7 2TB',
                'current_stock' => 90,
                'min_stock_level' => 18,
                'max_stock_level' => 400,
            ],
            [
                'product_code' => 'ELECT-010',
                'product_name' => 'Webcam Logitech Brio 4K',
                'current_stock' => 0, // Out of stock (akan trigger alert)
                'min_stock_level' => 5,
                'max_stock_level' => 100,
            ],
        ];

        foreach ($products as $product) {
            Inventory::create($product);
            
            $status = '';
            if ($product['current_stock'] == 0) {
                $status = ' [OUT OF STOCK]';
            } elseif ($product['current_stock'] <= $product['min_stock_level']) {
                $status = ' [LOW STOCK]';
            }
            
            $this->command->info(
                "Created product: {$product['product_code']} - {$product['product_name']} " .
                "(Stock: {$product['current_stock']}){$status}"
            );
        }
    }
}
