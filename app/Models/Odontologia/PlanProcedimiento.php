<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanProcedimiento extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_plan_procedimientos';
    protected $primaryKey = 'id_plan_procedimiento';
    protected $fillable = ['id_plan_tratamiento','id_detalle_origen','id_version_origen','id_servicio','codigo_servicio','nombre_servicio','pieza_fdi','caras','cantidad_requerida','cantidad_realizada','ultimo_avance_at','estado','orden'];
    protected $casts = ['cantidad_requerida' => 'integer', 'cantidad_realizada' => 'integer', 'ultimo_avance_at' => 'datetime'];

    public function plan(): BelongsTo { return $this->belongsTo(PlanTratamiento::class, 'id_plan_tratamiento', 'id_plan_tratamiento'); }
    public function detalleOrigen(): BelongsTo { return $this->belongsTo(PresupuestoDetalle::class, 'id_detalle_origen', 'id_detalle'); }
    public function sesiones(): HasMany { return $this->hasMany(TratamientoSesionDetalle::class, 'id_plan_procedimiento', 'id_plan_procedimiento'); }
}
