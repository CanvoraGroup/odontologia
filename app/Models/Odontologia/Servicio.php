<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $table = 'odo_servicios';
    protected $primaryKey = 'id_servicio';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo_servicio',
        'precio_base',
        'requiere_pago',
        'permite_cita',
        'duracion_minutos',
        'estado',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'requiere_pago' => 'boolean',
        'permite_cita' => 'boolean',
    ];

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_servicio', 'id_servicio');
    }
}