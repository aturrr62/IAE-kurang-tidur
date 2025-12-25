<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Product::insert([
            ['code' => 'ELEC001', 'name' => 'Smartphone Samsung Galaxy S23', 'category' => 'Smartphone', 'price' => 12000000, 'stock' => 50, 'min_stock_threshold' => 10],
            ['code' => 'ELEC002', 'name' => 'Laptop ASUS ROG Strix', 'category' => 'Laptop', 'price' => 25000000, 'stock' => 20, 'min_stock_threshold' => 5],
            ['code' => 'ELEC003', 'name' => 'Tablet iPad Air', 'category' => 'Tablet', 'price' => 9000000, 'stock' => 15, 'min_stock_threshold' => 5],
            ['code' => 'ELEC004', 'name' => 'Sony WH-1000XM5 Headphones', 'category' => 'Audio', 'price' => 5000000, 'stock' => 30, 'min_stock_threshold' => 8],
            ['code' => 'ELEC005', 'name' => 'Smartwatch Apple Watch Series 8', 'category' => 'Wearable', 'price' => 7000000, 'stock' => 25, 'min_stock_threshold' => 5],
            ['code' => 'ELEC006', 'name' => 'Fujifilm X-T5 Camera', 'category' => 'Camera', 'price' => 28000000, 'stock' => 10, 'min_stock_threshold' => 3],
            ['code' => 'ELEC007', 'name' => 'Monitor Dell UltraSharp 27', 'category' => 'Accessories', 'price' => 6000000, 'stock' => 40, 'min_stock_threshold' => 10],
            ['code' => 'ELEC008', 'name' => 'Keyboard Mechanical Keychron K2', 'category' => 'Accessories', 'price' => 1500000, 'stock' => 100, 'min_stock_threshold' => 20],
            ['code' => 'ELEC009', 'name' => 'Nintendo Switch OLED', 'category' => 'Gaming', 'price' => 5500000, 'stock' => 60, 'min_stock_threshold' => 10],
            ['code' => 'ELEC010', 'name' => 'PlayStation 5 Console', 'category' => 'Gaming', 'price' => 9500000, 'stock' => 5, 'min_stock_threshold' => 2],
        ]);
    }
}
