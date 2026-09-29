<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;

class Odontologo extends Model
{
    protected $table = 'odo_odontologos';

    protected $primaryKey = 'id_odontologo';

    protected $fillable = [
        'id_usuario',
        'id_clinica',
        'colegiatura',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'telefono',
        'correo',
        'estado',
    ];

    public function getNombreCompletoAttribute()
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }
}