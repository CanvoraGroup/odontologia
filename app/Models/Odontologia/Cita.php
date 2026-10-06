<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'estado_pago',
        'motivo_exoneracion',
        'fecha_exoneracion',
        'observacion',
        'id_usuario_creacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fecha_exoneracion' => 'datetime',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function odontologo(): BelongsTo
    {
        return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo');
    }

    public function consultorio(): BelongsTo
    {
        return $this->belongsTo(Consultorio::class, 'id_consultorio', 'id_consultorio');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }
    public function pagos(): HasMany
{
    return $this->hasMany(Pago::class, 'id_cita', 'id_cita');
}
}