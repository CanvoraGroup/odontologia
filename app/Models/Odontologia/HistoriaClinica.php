<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;

class HistoriaClinica extends Model
{
    protected $table = 'odo_historias_clinicas';
    protected $primaryKey = 'id_historia';

    protected $fillable = [
        'id_paciente',
        'id_odontologo',
        'fecha_atencion',
        'hora_atencion',
        'motivo_consulta',
        'antecedentes',
        'alergias',
        'enfermedades',
        'medicamentos_actuales',
        'examen_clinico',
        'diagnostico',
        'cie10_codigo',
        'indicaciones',
        'estado',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function odontologo()
    {
        return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo');
    }
    public function adjuntos()
{
    return $this->hasMany(HistoriaAdjunto::class, 'id_historia', 'id_historia');
}
}