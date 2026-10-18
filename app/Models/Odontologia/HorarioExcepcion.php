<?php

namespace App\Models\Odontologia;

use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class HorarioExcepcion extends Model
{
    use BelongsToClinic;

    protected $table = 'odo_horarios_excepciones';
    protected $primaryKey = 'id_excepcion';
    protected $fillable = [
        'id_clinica', 'id_horario', 'id_odontologo', 'fecha', 'tipo',
        'hora_inicio', 'hora_fin', 'id_consultorio', 'motivo', 'estado',
        'id_usuario_creacion', 'id_usuario_modificacion', 'id_usuario_cancelacion',
        'fecha_cancelacion', 'motivo_cancelacion',
    ];
    protected $casts = ['fecha' => 'date', 'fecha_cancelacion' => 'datetime'];

    public function horario(): BelongsTo
    {
        return $this->belongsTo(HorarioOdontologo::class, 'id_horario', 'id_horario');
    }
}
