<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::insert([
            [
                'username' => 'admin_gudang',
                'name' => 'Admin Gudang',
                'email' => 'admin@gudang.com',
                'password' => bcrypt('password123'),
                'role' => 'ADMIN_GUDANG',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'staff_warehouse',
                'name' => 'Staff Warehouse',
                'email' => 'staff@gudang.com',
                'password' => bcrypt('password123'),
                'role' => 'STAFF_GUDANG',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'supervisor',
                'name' => 'Supervisor Gudang',
                'email' => 'supervisor@gudang.com',
                'password' => bcrypt('password123'),
                'role' => 'ADMIN_GUDANG',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
