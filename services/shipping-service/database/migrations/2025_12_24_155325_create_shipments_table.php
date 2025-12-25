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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_order_id');
            $table->string('shipping_code', 50)->unique();
            $table->text('store_address');
            $table->timestamp('shipped_at')->nullable();
            $table->enum('status', ['SIAP_DIKIRIM', 'DIKIRIM', 'DITERIMA_TOKO'])->default('SIAP_DIKIRIM');
            $table->timestamps();

            $table->foreign('warehouse_order_id')->references('id')->on('warehouse_orders');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
