<?php

namespace App\Models\Proveedores;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    use HasFactory;

    protected $connection = 'proveedores';

    protected $guarded = [];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

}
