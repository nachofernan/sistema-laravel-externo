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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username'); // CUIT
            $table->string('ip_address');
            $table->enum('attempt_type', ['check_user', 'login', 'register_request']);
            $table->enum('result', ['success', 'failed', 'blocked']);
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Info adicional como email enviado, etc.
            $table->timestamps();
            
            $table->index(['username', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
