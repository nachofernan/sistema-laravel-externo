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
        Schema::create('eventos_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username'); // CUIT, denormalizado igual que login_attempts
            $table->enum('tipo_evento', [
                'login',
                'logout',
                'registro',
                'cambio_password',
                'bloqueo_cuenta',
                'desbloqueo_cuenta',
                'suspension_cuenta',
                'activacion_cuenta',
            ]);
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('detalle')->nullable();
            $table->timestamps();

            $table->index(['username', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['tipo_evento', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos_usuario');
    }
};
