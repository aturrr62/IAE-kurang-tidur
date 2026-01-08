<?php

namespace Database\Seeders;

use App\Models\ExternalApiKey;
use Illuminate\Database\Seeder;

class ExternalApiKeySeeder extends Seeder
{
    public function run(): void
    {
        ExternalApiKey::create([
            'client_name' => 'Toko ElectroMart',
            'api_key' => 'electromart_api_key_2024',
            'secret_key' => 'shared_secret_with_toko_12345',
            'is_active' => true,
            'expires_at' => date('Y-m-d', strtotime('+1 year')),
        ]);

        ExternalApiKey::create([
            'client_name' => 'Toko Elektronik Central',
            'api_key' => 'central_api_key_2024',
            'secret_key' => 'shared_secret_central_67890',
            'is_active' => true,
            'expires_at' => date('Y-m-d', strtotime('+1 year')),
        ]);

        ExternalApiKey::create([
            'client_name' => 'Test Store Dev',
            'api_key' => 'test_key_dev_123',
            'secret_key' => 'test_secret_dev_456',
            'is_active' => true,
            'expires_at' => date('Y-m-d', strtotime('+3 months')),
        ]);
    }
}
