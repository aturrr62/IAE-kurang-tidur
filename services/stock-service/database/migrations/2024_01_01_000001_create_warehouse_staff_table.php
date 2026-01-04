<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel warehouse_staff: Menyimpan data staff gudang dengan sistem auth terintegrasi
     * Role: admin, manager, staff
     * Department: inventory, shipping, both
     */
    public function up(): void
    {
        Schema::create('warehouse_staff', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password'); // bcrypt dengan cost factor 10
            $table->enum('role', ['admin', 'manager', 'staff'])->default('staff');
            $table->enum('department', ['inventory', 'shipping', 'both'])->default('inventory');
            $table->timestamps();
            
            // Indexes untuk performa query
            $table->index('username');
            $table->index('role');
            $table->index('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_staff');
    }
};
