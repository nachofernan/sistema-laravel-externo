<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registros del formulario de la 2° Ronda de Negocios de Energía y Minería (evento de un día).
     */
    public function up(): void
    {
        Schema::create('registros_formulario_provincia', function (Blueprint $table) {
            $table->id();
            $table->json('datos');
            $table->timestamp('perfil_completado_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_formulario_provincia');
    }
};
