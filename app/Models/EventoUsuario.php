<?php

// app/Models/EventoUsuario.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventoUsuario extends Model
{
    use HasFactory;

    protected $table = 'eventos_usuario';

    protected $fillable = [
        'user_id',
        'username',
        'tipo_evento',
        'ip_address',
        'user_agent',
        'detalle',
    ];

    protected $casts = [
        'detalle' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes para consultas comunes
    public function scopeForUsername($query, $username)
    {
        return $query->where('username', $username);
    }

    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_evento', $tipo);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }

    /**
     * Escritura centralizada: los puntos de origen están repartidos en varios
     * archivos (login, logout, registro, cambio de password, etc.), sin una
     * clase en común como sí tiene login_attempts con logAttempt().
     * Falla silenciosa a propósito: un evento de auditoría nunca debe romper
     * el flujo principal del usuario.
     */
    public static function registrar(
        string $tipoEvento,
        ?User $user = null,
        ?string $username = null,
        array $detalle = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): void {
        try {
            static::create([
                'user_id' => $user?->id,
                'username' => $username ?? $user?->username ?? 'desconocido',
                'tipo_evento' => $tipoEvento,
                'ip_address' => $ip ?? request()?->ip(),
                'user_agent' => $userAgent ?? request()?->userAgent(),
                'detalle' => $detalle,
            ]);
        } catch (\Exception $e) {
            // Log silencioso, mismo criterio que LoginAttempt::logAttempt()
        }
    }
}
