<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ExternalApiKey;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test API keys for external stores
        // Note: User table seeding is skipped for SQLite in-memory database
        // Only seed the ExternalApiKey table which is needed for tests
    }
}
