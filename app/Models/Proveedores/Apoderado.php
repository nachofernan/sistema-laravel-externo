<?php

namespace App\Models\Proveedores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apoderado extends Model
{
    use HasFactory;

    protected $guarded = false; // Abre todas las columnas del modelo
    protected $connection = 'proveedores'; // Setea la conexión a la base de datos

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function documentos($soloValidados = true)
    {
        if($soloValidados) {
            return $this->morphMany(Documento::class, 'documentable')->whereHas('validacion', function ($q) {
                $q->where('validado', true);
            });
        } else {
            return $this->morphMany(Documento::class, 'documentable');
        }
    }
}
