<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouse_orders', function (Blueprint $table) {
            $table->id();
            $table->string('toko_order_code');
            $table->string('store_code');
            $table->enum('status', ['MENUNGGU', 'DITERIMA', 'DIPROSES', 'DIKEMAS', 'SIAP_DIKIRIM', 'DIKIRIM', 'DITERIMA_TOKO', 'DITOLAK'])
                  ->default('MENUNGGU');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->enum('priority', ['LOW', 'NORMAL', 'HIGH', 'URGENT'])->default('NORMAL');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('estimated_delivery')->nullable();
            $table->timestamps();
            $table->index('toko_order_code');
            $table->index('status');
            $table->index('store_code');

            $table->foreign('processed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_orders');
    }
};
