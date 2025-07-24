<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prorroga extends Model
{
    use HasFactory;

    protected $guarded = false; // Abre todas las columnas del modelo
    protected $connection = 'concursos'; // Setea la conexión a la base de datos

    public function concurso() {
        return $this->belongsTo(Concurso::class); // Relación de uno a muchos con la tabla concursos
    }

    protected $casts = [
        'fecha_anterior' => 'datetime',
        'fecha_actual' => 'datetime',
    ];
}
