<?php

namespace App\Models\Proveedores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subrubro extends Model
{
    use HasFactory;

    protected $connection = 'proveedores';

    protected $guarded = [];

    public function rubro()
    {
        return $this->belongsTo(Rubro::class);
    }

    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class);
    }
}
