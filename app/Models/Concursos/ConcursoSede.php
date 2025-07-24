<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConcursoSede extends Model
{
    use HasFactory;

    protected $guarded = false; // Abre todas las columnas del modelo
    protected $connection = 'concursos'; // Setea la conexión a la base de datos
    protected $table = 'concurso_sede';

    public function concurso()
    {
        return $this->belongsTo(Concurso::class);
    }

    public function getNombreSedeAttribute()
    {
        $sedes = [
            1 => 'La Plata',
            2 => 'Mar del Plata',
            3 => 'Villa Gesell',
            4 => 'Mar de Ajó',
            5 => 'Necochea',
        ];

        return $sedes[$this->sede_id] ?? 'Sede no definida';
    }
}
