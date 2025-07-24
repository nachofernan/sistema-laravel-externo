<?php

namespace App\Models\Proveedores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rubro extends Model
{
    use HasFactory;

    protected $connection = 'proveedores';

    protected $guarded = [];

    public function subrubros()
    {
        return $this->hasMany(Subrubro::class);
    }
}
