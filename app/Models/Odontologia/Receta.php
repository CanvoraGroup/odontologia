<?php
namespace App\Models\Odontologia;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Receta extends Model {
    use BelongsToClinic; protected $table='odo_recetas'; protected $primaryKey='id_receta';
    protected $fillable=['id_atencion','id_paciente','id_odontologo','numero_receta','idempotency_key','version','fecha_emision','indicaciones_generales','estado','id_receta_anterior','id_usuario_creacion','id_usuario_modificacion','id_usuario_anulacion','fecha_anulacion','motivo_anulacion'];
    protected $casts=['fecha_emision'=>'date','fecha_anulacion'=>'datetime','version'=>'integer'];
    public function atencion(): BelongsTo{return $this->belongsTo(Atencion::class,'id_atencion','id_atencion');}
    public function paciente(): BelongsTo{return $this->belongsTo(Paciente::class,'id_paciente','id_paciente');}
    public function odontologo(): BelongsTo{return $this->belongsTo(Odontologo::class,'id_odontologo','id_odontologo');}
    public function detalles(): HasMany{return $this->hasMany(RecetaDetalle::class,'id_receta','id_receta')->orderBy('orden');}
    public function anterior(): BelongsTo{return $this->belongsTo(self::class,'id_receta_anterior','id_receta');}
}
