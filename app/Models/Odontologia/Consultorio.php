<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;

class Consultorio extends Model
{
    protected $table = 'odo_consultorios';

    protected $primaryKey = 'id_consultorio';

    protected $fillable = [
        'id_clinica',
        'nombre',
        'ubicacion',
        'estado',
    ];
}