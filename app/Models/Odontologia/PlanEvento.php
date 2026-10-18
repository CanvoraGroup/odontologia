<?php

namespace App\Models\Odontologia;

use Illuminate\Database\Eloquent\Model;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanEvento extends Model
{
    use BelongsToClinic;
    protected $table = 'odo_plan_eventos';
    protected $primaryKey = 'id_evento';
    protected $fillable = ['id_plan_tratamiento','id_version','tipo','estado_anterior','estado_nuevo','id_usuario','observacion','metadata'];
    protected $casts = ['metadata' => 'array'];
    public function plan(): BelongsTo { return $this->belongsTo(PlanTratamiento::class, 'id_plan_tratamiento', 'id_plan_tratamiento'); }
    public function version(): BelongsTo { return $this->belongsTo(PresupuestoVersion::class, 'id_version', 'id_version'); }
    public function usuario(): BelongsTo { return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario'); }
}
