<?php

// app/Models/LoginAttempt.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'ip_address',
        'attempt_type',
        'result',
        'user_agent',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Scopes para consultas comunes
    public function scopeSuccessful($query)
    {
        return $query->where('result', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('result', 'failed');
    }

    public function scopeBlocked($query)
    {
        return $query->where('result', 'blocked');
    }

    public function scopeForUsername($query, $username)
    {
        return $query->where('username', $username);
    }

    public function scopeForIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>', now()->subMinutes($minutes));
    }

    public function scopeLoginAttempts($query)
    {
        return $query->where('attempt_type', 'login');
    }

    public function scopeRegistrationAttempts($query)
    {
        return $query->where('attempt_type', 'register_request');
    }

    // Accessor para mostrar el tipo de manera legible
    public function getReadableTypeAttribute()
    {
        $types = [
            'check_user' => 'Verificación de Usuario',
            'login' => 'Intento de Login',
            'register_request' => 'Solicitud de Registro'
        ];

        return $types[$this->attempt_type] ?? $this->attempt_type;
    }

    // Accessor para mostrar el resultado de manera legible
    public function getReadableResultAttribute()
    {
        $results = [
            'success' => 'Exitoso',
            'failed' => 'Fallido',
            'blocked' => 'Bloqueado'
        ];

        return $results[$this->result] ?? $this->result;
    }

    // Métodos estáticos para estadísticas rápidas
    public static function getRecentFailedAttempts($minutes = 60)
    {
        return static::failed()
                    ->recent($minutes)
                    ->count();
    }

    public static function getTopFailedIps($limit = 10, $hours = 24)
    {
        return static::failed()
                    ->where('created_at', '>', now()->subHours($hours))
                    ->selectRaw('ip_address, count(*) as attempts')
                    ->groupBy('ip_address')
                    ->orderByDesc('attempts')
                    ->limit($limit)
                    ->get();
    }

    public static function getRecentRegistrationRequests($hours = 24)
    {
        return static::registrationAttempts()
                    ->where('created_at', '>', now()->subHours($hours))
                    ->orderByDesc('created_at')
                    ->get();
    }
}