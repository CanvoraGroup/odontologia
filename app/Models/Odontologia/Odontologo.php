<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class Odontologo extends Model
{
    use BelongsToClinic;
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
        'direccion',
        'estado',
        'observaciones',
        'id_usuario_desactivacion',
        'fecha_desactivacion',
        'motivo_desactivacion',
        'id_usuario_reactivacion',
        'fecha_reactivacion',
    ];

    protected $casts = [
        'fecha_desactivacion' => 'datetime',
        'fecha_reactivacion' => 'datetime',
    ];

    public function getNombreCompletoAttribute()
    {
        return trim($this->nombres . ' ' . $this->apellidos);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'odo_odontologo_especialidades', 'id_odontologo', 'id_especialidad');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(OdontologoDocumento::class, 'id_odontologo', 'id_odontologo');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioOdontologo::class, 'id_odontologo', 'id_odontologo');
    }

    public function bloqueos(): HasMany
    {
        return $this->hasMany(DisponibilidadBloqueo::class, 'id_odontologo', 'id_odontologo');
    }
}
