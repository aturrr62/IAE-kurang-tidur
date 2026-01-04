<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\WarehouseStaff;
use Illuminate\Support\Facades\Hash;

echo "=== CHECKING USERS ===\n\n";

$users = WarehouseStaff::all();

if ($users->isEmpty()) {
    echo "❌ NO USERS FOUND! Run: php artisan db:seed\n";
} else {
    $passwords = [
        'admin' => 'admin123',
        'manager_inv' => 'manager123',
        'staff_ship' => 'staff123',
        'staff_inv' => 'staff123',
    ];
    
    foreach ($users as $user) {
        $correctPassword = $passwords[$user->username] ?? 'password123';
        echo "Username: {$user->username}\n";
        echo "Name: {$user->name}\n";
        echo "Correct Password: {$correctPassword}\n";
        echo "Hash Check: " . (Hash::check($correctPassword, $user->password) ? '✅ MATCH' : '❌ NO MATCH') . "\n";
        echo "---\n";
    }
}

echo "\nTotal users: " . $users->count() . "\n";
