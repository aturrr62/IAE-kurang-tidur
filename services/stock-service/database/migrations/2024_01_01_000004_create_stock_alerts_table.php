<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel stock_alerts: Monitoring real-time untuk stok rendah/habis
     */
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('product_code');
            $table->enum('alert_type', ['low', 'out']); // low: di bawah min, out: habis
            $table->integer('current_stock');
            $table->boolean('resolved')->default(false); // Apakah sudah ditangani
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable(); // Staff yang menangani
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('product_code')
                  ->references('product_code')
                  ->on('inventory')
                  ->onDelete('cascade');
                  
            $table->foreign('resolved_by')
                  ->references('id')
                  ->on('warehouse_staff')
                  ->onDelete('set null');
            
            // Indexes
            $table->index('product_code');
            $table->index('alert_type');
            $table->index('resolved');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
