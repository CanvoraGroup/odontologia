<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Odontologia\Odontograma;
use App\Models\Odontologia\Cita;

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

    
public function odontogramas(): HasMany
{
    return $this->hasMany(Odontograma::class, 'id_paciente', 'id_paciente');
}

public function citas(): HasMany
{
    return $this->hasMany(Cita::class, 'id_paciente', 'id_paciente');
}
}