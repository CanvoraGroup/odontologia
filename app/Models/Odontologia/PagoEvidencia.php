<?php
namespace App\Models\Odontologia;
use App\Models\Odontologia\Concerns\BelongsToClinic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PagoEvidencia extends Model {
    use BelongsToClinic; protected $table='odo_pago_evidencias'; protected $primaryKey='id_evidencia';
    protected $fillable=['id_pago','ruta_privada','nombre_original','mime_type','tamano_bytes','hash_sha256','estado','id_usuario_creacion','id_usuario_modificacion','id_usuario_retiro','fecha_retiro','motivo_retiro'];
    protected $casts=['fecha_retiro'=>'datetime']; public function pago(): BelongsTo{return $this->belongsTo(Pago::class,'id_pago','id_pago');}
    public function usuarioCreacion(): BelongsTo{return $this->belongsTo(Usuario::class,'id_usuario_creacion','id_usuario');}
}
