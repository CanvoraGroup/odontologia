<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Odontograma extends Model
{
    protected $table = 'odo_odontogramas';

    protected $primaryKey = 'id_odontograma';

    protected $fillable = [
        'id_paciente',
        'id_odontologo',
        'fecha_registro',
        'tipo_denticion',
        'estado',
        'observacion_general',
        'id_usuario_creacion',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OdontogramaDetalle::class, 'id_odontograma', 'id_odontograma');
    }
}