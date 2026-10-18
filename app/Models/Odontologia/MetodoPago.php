<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'odo_metodos_pago';
    protected $primaryKey = 'id_metodo_pago';
    protected $fillable = ['nombre', 'estado'];
}
