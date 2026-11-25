<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroFormularioProvincia extends Model
{
    protected $table = 'registros_formulario_provincia';

    protected $fillable = [
        'datos',
        'perfil_completado_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos' => 'array',
        'perfil_completado_at' => 'datetime',
    ];
}
