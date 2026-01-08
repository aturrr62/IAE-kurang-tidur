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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_order_id')->constrained()->onDelete('cascade');
            $table->string('product_code');
            $table->string('product_name')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->enum('status', ['PENDING', 'RESERVED', 'PICKED', 'PACKED', 'SHIPPED', 'DELIVERED', 'CANCELLED'])
                  ->default('PENDING');
            $table->timestamps();
            $table->index('product_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
