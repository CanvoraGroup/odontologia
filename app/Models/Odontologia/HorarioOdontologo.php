<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class HorarioOdontologo extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_horarios_odontologos';
    protected $primaryKey = 'id_horario';
    protected $fillable = ['id_clinica', 'id_odontologo', 'id_consultorio', 'dia_semana', 'hora_inicio', 'hora_fin', 'fecha_desde', 'fecha_hasta', 'repetir_semanal', 'grupo_repeticion', 'estado', 'id_usuario_creacion', 'id_usuario_modificacion'];

    protected $casts = ['fecha_desde' => 'date', 'fecha_hasta' => 'date', 'repetir_semanal' => 'boolean'];

    public function odontologo(): BelongsTo { return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo'); }
    public function consultorio(): BelongsTo { return $this->belongsTo(Consultorio::class, 'id_consultorio', 'id_consultorio'); }
}
