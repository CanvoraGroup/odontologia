<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresupuestoVersion extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_presupuesto_versiones';
    protected $primaryKey = 'id_version';
    protected $fillable = ['id_plan_tratamiento','numero_version','estado','vigencia_hasta','condiciones','total','id_usuario_creacion','id_usuario_envio','enviado_at','id_usuario_respuesta','respondido_at','observacion_respuesta','lock_version'];
    protected $casts = ['vigencia_hasta' => 'date', 'enviado_at' => 'datetime', 'respondido_at' => 'datetime', 'total' => 'decimal:2'];
    public function plan(): BelongsTo { return $this->belongsTo(PlanTratamiento::class, 'id_plan_tratamiento', 'id_plan_tratamiento'); }
    public function usuarioRespuesta(): BelongsTo { return $this->belongsTo(Usuario::class, 'id_usuario_respuesta', 'id_usuario'); }
    public function detalles(): HasMany { return $this->hasMany(PresupuestoDetalle::class, 'id_version', 'id_version'); }
}
