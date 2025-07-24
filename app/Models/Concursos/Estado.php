<?php

namespace App\Models\Concursos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;

    protected $guarded = false; // Abre todas las columnas del modelo
    protected $connection = 'concursos'; // Setea la conexión a la base de datos

    public function concursos() {
        return $this->hasMany(Concurso::class); // Relación de uno a muchos con la tabla concursos
    }
}
