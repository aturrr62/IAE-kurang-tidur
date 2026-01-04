<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel stock_transactions: Audit trail untuk semua perubahan stok
     * Action: INCREMENT (tambah), DECREMENT (kurang), SET (koreksi langsung)
     */
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('product_code');
            $table->integer('quantity'); // Bisa positif atau negatif
            $table->enum('action', ['INCREMENT', 'DECREMENT', 'SET', 'RESERVE', 'RELEASE']);
            $table->text('note')->nullable(); // Keterangan transaksi
            $table->unsignedBigInteger('staff_id')->nullable(); // Siapa yang melakukan
            $table->integer('stock_before')->default(0); // Stok sebelum transaksi
            $table->integer('stock_after')->default(0); // Stok sesudah transaksi
            $table->timestamp('created_at')->useCurrent();
            
            // Foreign keys
            $table->foreign('product_code')
                  ->references('product_code')
                  ->on('inventory')
                  ->onDelete('cascade');
                  
            $table->foreign('staff_id')
                  ->references('id')
                  ->on('warehouse_staff')
                  ->onDelete('set null');
            
            // Indexes
            $table->index('product_code');
            $table->index('staff_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
