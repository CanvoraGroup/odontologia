<?php

namespace App\Models\Odontologia;

use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisponibilidadBloqueo extends Model
{
    use BelongsToClinic;

    protected $table = 'odo_bloqueos_odontologos';
    protected $primaryKey = 'id_bloqueo';
    protected $fillable = ['id_clinica', 'id_odontologo', 'fecha_desde', 'fecha_hasta', 'hora_desde', 'hora_hasta', 'dia_completo', 'motivo', 'estado', 'id_usuario_creacion', 'id_usuario_modificacion', 'id_usuario_cancelacion', 'fecha_cancelacion', 'motivo_cancelacion'];
    protected $casts = ['fecha_desde' => 'date', 'fecha_hasta' => 'date', 'dia_completo' => 'boolean', 'fecha_cancelacion' => 'datetime'];

    public function odontologo(): BelongsTo { return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo'); }
}
