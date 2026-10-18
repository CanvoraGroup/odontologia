<?php
namespace App\Models\Odontologia;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RecetaDetalle extends Model {
    use BelongsToClinic; protected $table='odo_receta_detalles'; protected $primaryKey='id_receta_detalle';
    protected $fillable=['id_receta','orden','medicamento_texto','presentacion','dosis','via_administracion','frecuencia','duracion','cantidad','unidad','indicacion','id_usuario_creacion','id_usuario_modificacion'];
    protected $casts=['cantidad'=>'decimal:2']; public function receta(): BelongsTo{return $this->belongsTo(Receta::class,'id_receta','id_receta');}
    public function getMedicamentoAttribute(): ?string { return $this->attributes['medicamento_texto'] ?? null; }
    public function getIndicacionesAttribute(): ?string { return $this->attributes['indicacion'] ?? null; }
}
