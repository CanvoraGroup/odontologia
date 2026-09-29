<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    protected $table = 'odo_citas';

    protected $primaryKey = 'id_cita';

    protected $fillable = [
        'id_paciente',
        'id_odontologo',
        'id_consultorio',
        'id_servicio',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'estado',
        'observacion',
        'id_usuario_creacion',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function odontologo()
    {
        return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo');
    }

    public function consultorio()
    {
        return $this->belongsTo(Consultorio::class, 'id_consultorio', 'id_consultorio');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }
}