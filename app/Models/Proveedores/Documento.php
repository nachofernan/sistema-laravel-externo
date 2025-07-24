<?php

namespace App\Models\Proveedores;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $connection = 'proveedores';

    protected $guarded = [];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function documentoTipo()
    {
        return $this->belongsTo(DocumentoTipo::class, 'documento_tipo_id');
    }

    protected $casts = [
        'vencimiento' => 'datetime',
    ];

    public function validacion()
    {
        return $this->hasOne(Validacion::class);
    }
}
