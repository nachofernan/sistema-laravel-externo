<?php

namespace App\Models\Concursos;

use App\Models\Proveedores\Proveedor;
use App\Models\Proveedores\Subrubro;
use App\Models\Pivots\ConcursoProveedor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Concurso extends Model
{
    use HasFactory;

    protected $connection = 'concursos';

    protected $guarded = false;

    
    public function invitaciones()
    {
        return $this->hasMany(Invitacion::class);
    }

    public function invitacion()
    {
        return Invitacion::where('concurso_id', $this->id)->where('proveedor_id', Auth::user()->proveedor->id)->first();
    }

    public function estado() {
        return $this->belongsTo(Estado::class);
    }

    public function subrubro() {
        return $this->belongsTo(Subrubro::class);
    }

    public function encargado() {
        return $this->belongsTo(User::class, 'encargado_user_id');
    }

    public function tecnico() {
        return $this->belongsTo(User::class, 'tecnico_user_id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class);
    }

    public function prorrogas()
    {
        return $this->hasMany(Prorroga::class);
    }

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function documentos_requeridos()
    {
        return $this->belongsToMany(DocumentoTipo::class, 'concurso_documento_tipo', 'concurso_id', 'documento_tipo_id')->orderByPivot('documento_tipo_id');
    }

    public function contactos()
    {
        return $this->hasMany(Contacto::class)->orderBy('tipo');
    }

    public function sedes()
    {
        return $this->hasMany(ConcursoSede::class);
    }
}
