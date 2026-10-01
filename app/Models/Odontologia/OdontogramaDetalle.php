<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdontogramaDetalle extends Model
{
    protected $table = 'odo_odontograma_detalles';

    protected $primaryKey = 'id_odontograma_detalle';

protected $fillable = [
    'id_odontograma',
    'pieza_fdi',
    'cara_dental',
    'condicion',
    'diagnostico_cie10',
    'diagnostico_descripcion',
    'procedimiento_sugerido',
    'observacion',
    'estado',
];

    public function odontograma(): BelongsTo
    {
        return $this->belongsTo(Odontograma::class, 'id_odontograma', 'id_odontograma');
    }
}