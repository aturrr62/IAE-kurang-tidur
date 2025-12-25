<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         \App\Models\User::create([
            'name' => 'Staff Gudang Pusat',
            'email' => 'gudang@example.com',
            'password' => bcrypt('password'),
        ]);
    }
}
