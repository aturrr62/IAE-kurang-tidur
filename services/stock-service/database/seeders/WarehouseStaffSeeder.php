<?php

namespace Database\Seeders;

use App\Models\WarehouseStaff;
use Illuminate\Database\Seeder;

/**
 * WarehouseStaffSeeder
 * 
 * Seed minimal 3 user staff:
 * 1. Admin (full access)
 * 2. Manager Inventory (inventory management)
 * 3. Staff Shipping (shipping operations)
 */
class WarehouseStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffData = [
            [
                'username' => 'admin',
                'name' => 'Administrator',
                'email' => 'admin@warehouse.com',
                'password' => 'admin123', // Akan otomatis di-hash oleh model
                'role' => 'admin',
                'department' => 'both',
            ],
            [
                'username' => 'manager_inv',
                'name' => 'Inventory Manager',
                'email' => 'manager.inventory@warehouse.com',
                'password' => 'manager123',
                'role' => 'manager',
                'department' => 'inventory',
            ],
            [
                'username' => 'staff_ship',
                'name' => 'Shipping Staff',
                'email' => 'staff.shipping@warehouse.com',
                'password' => 'staff123',
                'role' => 'staff',
                'department' => 'shipping',
            ],
            [
                'username' => 'staff_inv',
                'name' => 'Inventory Staff',
                'email' => 'staff.inventory@warehouse.com',
                'password' => 'staff123',
                'role' => 'staff',
                'department' => 'inventory',
            ],
        ];

        foreach ($staffData as $staff) {
            WarehouseStaff::create($staff);
            $this->command->info("Created staff: {$staff['username']} ({$staff['role']})");
        }
    }
}
