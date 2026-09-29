<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $table = 'odo_pacientes';
    protected $primaryKey = 'id_paciente';

    protected $fillable = [
        'id_clinica',
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'correo',
        'direccion',
        'contacto_emergencia',
        'telefono_emergencia',
        'observacion',
        'estado',
        'id_usuario_creacion',
    ];
}