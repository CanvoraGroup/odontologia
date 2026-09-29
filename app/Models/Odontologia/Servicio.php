<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'odo_servicios';

    protected $primaryKey = 'id_servicio';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'precio_base',
        'duracion_minutos',
        'estado',
    ];
}