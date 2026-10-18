<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class Servicio extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_servicios';
    protected $primaryKey = 'id_servicio';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'id_categoria',
        'tipo_servicio',
        'precio_base',
        'forma_cobro',
        'requiere_pieza',
        'requiere_caras',
        'sesiones_estimadas',
        'requiere_pago',
        'permite_cita',
        'duracion_minutos',
        'estado',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'requiere_pieza' => 'boolean',
        'requiere_caras' => 'boolean',
        'requiere_pago' => 'boolean',
        'permite_cita' => 'boolean',
    ];

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_servicio', 'id_servicio');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaServicio::class, 'id_categoria', 'id_categoria');
    }
}
