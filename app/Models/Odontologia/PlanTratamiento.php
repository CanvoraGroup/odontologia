<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Odontologia\Concerns\BelongsToClinic;

class PlanTratamiento extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_planes_tratamiento';
    protected $primaryKey = 'id_plan_tratamiento';

    protected $fillable = ['numero', 'idempotency_key', 'id_paciente', 'id_odontologo', 'id_atencion', 'nombre', 'tipo_plan', 'fecha_plan', 'descripcion', 'observaciones', 'monto_total', 'estado', 'estado_clinico', 'id_usuario_creacion', 'lock_version'];
    protected $casts = ['fecha_plan' => 'date', 'monto_total' => 'decimal:2'];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente'); }
    public function odontologo(): BelongsTo { return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo'); }
    public function atencion(): BelongsTo { return $this->belongsTo(Atencion::class, 'id_atencion', 'id_atencion'); }
    public function versiones(): HasMany { return $this->hasMany(PresupuestoVersion::class, 'id_plan_tratamiento', 'id_plan_tratamiento')->orderByDesc('numero_version'); }
    public function eventos(): HasMany { return $this->hasMany(PlanEvento::class, 'id_plan_tratamiento', 'id_plan_tratamiento')->latest(); }
    public function procedimientosClinicos(): HasMany { return $this->hasMany(PlanProcedimiento::class, 'id_plan_tratamiento', 'id_plan_tratamiento')->orderBy('orden')->orderBy('id_plan_procedimiento'); }
    public function sesionesTratamiento(): HasMany { return $this->hasMany(TratamientoSesion::class, 'id_plan_tratamiento', 'id_plan_tratamiento')->orderByDesc('numero_sesion'); }
    public function pagos(): HasMany { return $this->hasMany(Pago::class, 'id_plan_tratamiento', 'id_plan_tratamiento'); }
    public function getVersionActualAttribute(): ?PresupuestoVersion { return $this->versiones->first(); }
}
