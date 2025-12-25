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
            $table->string('toko_order_code', 50);
            $table->string('product_code', 50);
            $table->integer('quantity');
            $table->enum('status', ['MENUNGGU', 'DITERIMA', 'DITOLAK'])->default('MENUNGGU');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
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
