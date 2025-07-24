<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Proveedores\Proveedor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasProfilePhoto, Notifiable, TwoFactorAuthenticatable;

    protected $guarded = false;

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'registered_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    // Relaciones
    public function proveedor()
    {
        return $this->hasOne(Proveedor::class, 'cuit', 'username');
    }

    public function loginAttempts()
    {
        return $this->hasMany(LoginAttempt::class, 'username', 'username');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    // Métodos auxiliares
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until > now();
    }

    public function unlock(): void
    {
        $this->update([
            'locked_until' => null,
            'failed_login_attempts' => 0
        ]);
    }

    public function suspend(string $reason = null): void
    {
        $this->update(['status' => 'suspended']);
        
        // Registrar en login_attempts si se quiere trackear
        LoginAttempt::create([
            'username' => $this->username,
            'ip_address' => request()->ip() ?? 'system',
            'attempt_type' => 'admin_action',
            'result' => 'suspended',
            'metadata' => ['reason' => $reason, 'admin_user' => Auth::user()->id]
        ]);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }
}
