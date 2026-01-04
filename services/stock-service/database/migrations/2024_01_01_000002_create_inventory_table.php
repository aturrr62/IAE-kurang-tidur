<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel inventory: Master data barang dengan monitoring stok min/max
     */
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('product_code')->unique(); // SKU unik
            $table->string('product_name');
            $table->integer('current_stock')->default(0); // Stok saat ini
            $table->integer('min_stock_level')->default(10); // Threshold minimum
            $table->integer('max_stock_level')->default(1000); // Kapasitas maksimum
            $table->timestamps();
            
            // Indexes untuk performa
            $table->index('product_code');
            $table->index('current_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
