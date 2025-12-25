<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Inventory::insert([
            ['product_code' => 'ELEC001', 'product_name' => 'Smartphone Samsung Galaxy S23', 'stock' => 500],
            ['product_code' => 'ELEC002', 'product_name' => 'Laptop ASUS ROG Strix', 'stock' => 200],
            ['product_code' => 'ELEC010', 'product_name' => 'PlayStation 5 Console', 'stock' => 50],
        ]);
    }
}
