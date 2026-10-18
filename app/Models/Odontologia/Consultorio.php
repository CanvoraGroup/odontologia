<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class Consultorio extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_consultorios';

    protected $primaryKey = 'id_consultorio';

    protected $fillable = [
        'id_clinica',
        'nombre',
        'ubicacion',
        'estado',
    ];
}
