<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TratamientoSesionDetalle extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_tratamiento_sesion_detalles';
    protected $primaryKey = 'id_sesion_detalle';
    protected $fillable = ['id_sesion','id_plan_procedimiento','cantidad_realizada','estado_resultante','evolucion'];
    protected $casts = ['cantidad_realizada' => 'integer'];
    public function sesion(): BelongsTo { return $this->belongsTo(TratamientoSesion::class, 'id_sesion', 'id_sesion'); }
    public function procedimiento(): BelongsTo { return $this->belongsTo(PlanProcedimiento::class, 'id_plan_procedimiento', 'id_plan_procedimiento'); }
}
