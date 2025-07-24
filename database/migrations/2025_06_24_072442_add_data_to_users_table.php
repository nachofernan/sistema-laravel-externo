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
        Schema::table('users', function (Blueprint $table) {
            // Estado de la cuenta
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('email_verified_at');
            
            // Tracking de accesos
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->timestamp('registered_at')->nullable()->after('last_login_at');
            
            // Seguridad
            $table->integer('failed_login_attempts')->default(0)->after('registered_at');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
            
            // Información adicional
            $table->string('registration_ip')->nullable()->after('locked_until');
            $table->string('last_login_ip')->nullable()->after('registration_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn([
                'status', 'last_login_at', 'registered_at', 
                'failed_login_attempts', 'locked_until',
                'registration_ip', 'last_login_ip'
            ]);
        });
    }
};
