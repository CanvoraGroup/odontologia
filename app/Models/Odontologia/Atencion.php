<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class Atencion extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_atenciones';

    protected $primaryKey = 'id_atencion';

    protected $fillable = [
        'id_cita',
        'id_paciente',
        'id_odontologo',
        'fecha_atencion',
        'motivo_consulta',
        'examen_clinico',
        'diagnostico_general',
        'evolucion',
        'indicaciones',
        'estado',
        'id_usuario_creacion',
    ];

    protected $casts = [
        'fecha_atencion' => 'datetime',
    ];

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function odontologo(): BelongsTo
    {
        return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo');
    }
}
