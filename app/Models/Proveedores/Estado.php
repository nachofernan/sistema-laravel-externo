<?php

namespace App\Models\Proveedores;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    use HasFactory;

    protected $connection = 'proveedores';

    protected $guarded = [];
}
