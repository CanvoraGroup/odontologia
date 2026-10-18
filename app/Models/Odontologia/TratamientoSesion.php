<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TratamientoSesion extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_tratamiento_sesiones';
    protected $primaryKey = 'id_sesion';
    protected $fillable = ['idempotency_key','id_plan_tratamiento','numero_sesion','fecha_sesion','id_odontologo','id_atencion','estado','observaciones','id_usuario_creacion','id_usuario_finalizacion','finalizada_at','lock_version'];
    protected $casts = ['fecha_sesion' => 'datetime', 'finalizada_at' => 'datetime'];

    public function plan(): BelongsTo { return $this->belongsTo(PlanTratamiento::class, 'id_plan_tratamiento', 'id_plan_tratamiento'); }
    public function odontologo(): BelongsTo { return $this->belongsTo(Odontologo::class, 'id_odontologo', 'id_odontologo'); }
    public function atencion(): BelongsTo { return $this->belongsTo(Atencion::class, 'id_atencion', 'id_atencion'); }
    public function detalles(): HasMany { return $this->hasMany(TratamientoSesionDetalle::class, 'id_sesion', 'id_sesion'); }
    public function usuarioCreacion(): BelongsTo { return $this->belongsTo(Usuario::class, 'id_usuario_creacion', 'id_usuario'); }
    public function usuarioFinalizacion(): BelongsTo { return $this->belongsTo(Usuario::class, 'id_usuario_finalizacion', 'id_usuario'); }
}
