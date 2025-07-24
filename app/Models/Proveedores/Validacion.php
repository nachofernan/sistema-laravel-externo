<?php

namespace App\Models\Proveedores;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validacion extends Model
{
    use HasFactory;

    protected $guarded = false; // Abre todas las columnas del modelo
    protected $connection = 'proveedores'; // Setea la conexión a la base de datos

    // Datos en la base de datos
    // $table->string('nombre');
    // $table->enum('tipo', ['representante','apoderado']);
    // $table->boolean('activo')->default(true);
    // $table->unsignedBigInteger('proveedor_id');

    public function documento()
    {
        return $this->belongsTo(Documento::class);
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
