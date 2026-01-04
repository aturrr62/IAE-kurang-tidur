<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabel jwt_blacklist: Untuk mekanisme logout (invalidate token)
     */
    public function up(): void
    {
        Schema::create('jwt_blacklist', function (Blueprint $table) {
            $table->id();
            $table->text('token'); // JWT token yang di-blacklist
            $table->string('jti')->unique(); // JWT ID (unique identifier)
            $table->timestamp('expires_at'); // Kapan token expired
            $table->timestamp('blacklisted_at')->useCurrent();
            
            // Index
            $table->index('jti');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jwt_blacklist');
    }
};
